<?php

namespace Honeygavi\Validator;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandInterface;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Honeybee\Model\Event\AggregateRootEventList;
use Honeybee\Model\Task\TaskConflict;
use Trellis\Runtime\Entity\EntityInterface;
use Trellis\Runtime\Validator\Rule\Type\IntegerRule;

class AggregateRootCommandValidator extends AggregateRootTypeCommandValidator
{
    protected function validate()
    {
        $aggregate_root_history = $this->loadAggregateRootHistory();
        if ($aggregate_root_history->isEmpty()) {
            $this->throwError('history_is_empty');
            return false;
        }

        $revision = $this->getProvidedAggregatedRootRevision();

        if ($revision === null) {
            if ($this->getParameter('force_revision', true)) {
                $this->throwError('missing_revision');
                return false;
            } else {
                $revision = $aggregate_root_history->getLast()->getSeqNumber();
            }
        }

        // don't accept future non-existent revisions
        if ($aggregate_root_history->getLast()->getSeqNumber() < $revision) {
            $this->throwError('invalid_revision');
            return false;
        }

        $aggregate_root = $this->createAggregateRootFromHistory($aggregate_root_history, $revision);

        // build the command from the request and AR
        $request_payload = (array)$this->getData(null);
        $command_values = (array)$this->getValidatedCommandValues($request_payload, $aggregate_root);

        // no need to build the command if there were incidents
        if (count($this->parentContainer->getValidatorIncidents($this->getParameter('name'))) > 0
            || isset($this->incident)
        ) {
            return false;
        }

        $command = $this->buildCommand($command_values, $aggregate_root);
        if (!$command instanceof AggregateRootCommandInterface) {
            return false;
        }

        $conflicting_changes = [];
        $conflicting_events = $aggregate_root_history->reverse()->filter(
            function (AggregateRootEventInterface $event) use ($command, &$conflicting_changes) {
                return $event->getSeqNumber() > $command->getKnownRevision()
                    && $command->conflictsWith($event, $conflicting_changes);
            }
        );

        if (!$conflicting_events->isEmpty()) {
            $this->populateAggregateRootTaskConflict(
                $aggregate_root,
                $aggregate_root_history,
                $conflicting_events,
                $conflicting_changes
            );
            $this->export($conflicting_events, 'conflict_detected');
            $this->throwError('conflict_detected');
            return false;
        }

        $this->export($command, $this->getParameter('export', 'command'));

        return true;
    }

    protected function getProvidedAggregatedRootRevision()
    {
        $revision_arg = $this->getParameter('revision_arg', 'revision');
        $revision = $this->getData($revision_arg);

        if ($revision) {
            $number_rule = new IntegerRule('valid_revision', [ IntegerRule::OPTION_MIN_VALUE => 1 ]);
            if ($number_rule->apply($revision)) {
                return $number_rule->getSanitizedValue();
            }
        }

        return null;
    }

    protected function loadAggregateRootHistory()
    {
        $query_service = $this->getDomainEventQueryService();
        $query_result = $query_service->findEventsByIdentifier($this->getIdentifier());

        return new AggregateRootEventList($query_result->getResults());
    }

    protected function getIdentifier()
    {
        if (!$this->hasParameter('identifier_arg')) {
            throw new RuntimeError('Missing required parameter "identifier_arg".');
        }

        $identifier_arg = $this->getParameter('identifier_arg');
        $identifier = $this->getData($identifier_arg);
        if (!$identifier) {
            throw new RuntimeError(
                sprintf('Missing required payload for argument: "%s".', $identifier_arg)
            );
        }

        if ($identifier instanceof EntityInterface) {
            $identifier = $identifier->getIdentifier();
        }

        return $identifier;
    }

    protected function createAggregateRootFromHistory(AggregateRootEventList $history, $target_revision = null)
    {
        $target_revision = $target_revision ?: $history->getLast()->getSeqNumber();

        if ($history->getLast()->getSeqNumber() > $target_revision) {
            $known_history = $history->filter(
                function (AggregateRootEventInterface $event) use ($target_revision) {
                    return $event->getSeqNumber() <= $target_revision;
                }
            );
        } else {
            $known_history = $history;
        }

        $aggregate_root = $this->getAggregateRootType()->createEntity();
        $aggregate_root->reconstituteFrom($known_history);

        return $aggregate_root;
    }

    protected function populateAggregateRootTaskConflict(
        AggregateRootInterface $aggregate_root,
        AggregateRootEventList $aggregate_root_history,
        AggregateRootEventList $conflicting_events,
        array $conflicting_changes
    ) {
        $service_locator = $this->getContext()->getServiceLocator();
        $projection_type = $service_locator
            ->getProjectionTypeMap()
            ->getByAggregateRootType($this->getAggregateRootType());

        $conflicted_projection = $projection_type->createEntity(
            array_merge($aggregate_root->toNative(), $conflicting_changes)
        );

        $current_aggregate_root = $this->createAggregateRootFromHistory($aggregate_root_history);
        $timestamps['created_at'] = $aggregate_root_history->getFirst()->getIsoDate();
        $timestamps['modified_at'] = $aggregate_root_history->getLast()->getIsoDate();
        $current_projection = $projection_type->createEntity(array_merge(
            $current_aggregate_root->toNative(),
            $timestamps
        ));

        $task_conflict = new TaskConflict(
            [
                'current_resource' => $current_projection,
                'conflicted_resource' => $conflicted_projection,
                'conflicting_events' => $conflicting_events,
                'conflicting_attribute_names' => array_keys($conflicting_changes)
            ]
        );

        $service_locator->getTaskService()->addTaskConflict($task_conflict);
    }

    protected function getDomainEventQueryService()
    {
        $data_access_service = $this->getContext()->getServiceLocator()->getDataAccessService();
        $query_service_map = $data_access_service->getQueryServiceMap();

        return $query_service_map->getItem('honeybee::domain_event::query_service');
    }
}
