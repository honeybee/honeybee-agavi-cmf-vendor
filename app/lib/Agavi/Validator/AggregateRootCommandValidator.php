<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviValidationIncident;
use AgaviValidator;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\Logging\LogTrait;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandInterface;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Honeybee\Model\Event\AggregateRootEventList;
use Honeybee\Model\Task\TaskConflict;
use Trellis\Common\Collection\Map;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Entity\EntityInterface;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\Validator\Result\IncidentInterface;
use Trellis\Runtime\Validator\Rule\Type\IntegerRule;

class AggregateRootCommandValidator extends AggregateRootTypeCommandValidator
{
    use LogTrait;

    protected function validate()
    {
        $aggregate_root_history = $this->loadAggregateRootHistory();
        if ($aggregate_root_history->isEmpty()) {
            $this->logError('No history found for identifier', $this->getIdentifier(), '– Possible duplicate id?');
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

        if ($aggregate_root_history->getLast()->getSeqNumber() < $revision) {
            $this->throwError('invalid_revision');
            return false;
        }

        $aggregate_root = $this->createAggregateRootFromHistory($aggregate_root_history, $revision);
        $command_payload = $this->getValidatedAggregateRootCommandPayload($aggregate_root);

        if (!is_array($command_payload)) {
            return false;
        }

        if (count($this->parentContainer->getValidatorIncidents($this->getParameter('name'))) > 0) {
            foreach ($this->parentContainer->getValidatorIncidents($this->getParameter('name')) as $incident) {
                foreach ($incident->getErrors() as $error) {
                    $this->logDebug('Validator "' . $this->getParameter('name') . '" error: ' . $error->getMessage());
                }
            }
            $this->logDebug('About to throw "invalid_payload" error in validator: ' . $this->getParameter('name'));
            $this->throwError('invalid_payload');
            $this->getDependencyManager()->addDependTokens([ 'invalid_payload' ], $this->getBase());
            return false;
        }

        $command = $this->createAggregateRootCommand($command_payload, $aggregate_root);

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
        $query_service = $this->getQueryService();
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

        $aggregate_root = $this->aggregate_root_type->createEntity();
        $aggregate_root->reconstituteFrom($known_history);

        return $aggregate_root;
    }

    protected function createAggregateRootCommand(array $command_payload, AggregateRootInterface $aggregate_root)
    {
        $command_implementor = $this->getCommandImplementor();
        $command = new $command_implementor(
            array_merge(
                $command_payload,
                [
                    'aggregate_root_type' => $this->aggregate_root_type->getPrefix(),
                    'aggregate_root_identifier' => $aggregate_root->getIdentifier(),
                    'known_revision' => $aggregate_root->getRevision()
                ]
            )
        );

        if (!$command instanceof AggregateRootCommandInterface) {
            throw new RuntimeError(
                sprintf(
                    'The configured command type must implement %s, but the given command "%s" does not do so.',
                    AggregateRootCommandInterface::CLASS,
                    get_class($command)
                )
            );
        }

        return $command;
    }

    protected function populateAggregateRootTaskConflict(
        AggregateRootInterface $aggregate_root,
        AggregateRootEventList $aggregate_root_history,
        AggregateRootEventList $conflicting_events,
        array $conflicting_changes
    ) {
        $service_locator = $this->getServiceLocator();
        $projection_type = $service_locator->getProjectionTypeByPrefix($this->getAggregateRootType()->getPrefix());

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

    protected function getQueryService()
    {
        $projection_type_map = $this->getServiceLocator()->getProjectionTypeMap();
        $data_access_service = $this->getServiceLocator()->getDataAccessService();
        $query_service_map = $data_access_service->getQueryServiceMap();

        return $query_service_map->getByProjectionType(
            $projection_type_map->getItem($this->getAggregateRootType()->getPrefix())
        );
    }
}
