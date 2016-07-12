<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandBuilder;
use Honeybee\Projection\WorkflowSubject;

class ProceedWorkflowCommandValidator extends AggregateRootCommandValidator
{
    protected function getValidatedCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        $event_argument = $this->getArgument();
        if (!isset($request_payload[$event_argument])) {
            $this->throwError('missing_workflow_event');
            return false;
        }

        $state_machine = $aggregate_root->getType()->getWorkflowStateMachine();
        $current_state_name = $aggregate_root->getWorkflowState();
        $supported_events = WorkflowSubject::getSupportedEventsFor($state_machine, $current_state_name);

        $event_name = $request_payload[$event_argument];
        if (!in_array($event_name, $supported_events)) {
            $this->throwError('invalid_workflow_event');
            return false;
        }

        // no need to filter payload as we are not building the command with values
        return [ 'event' => $event_name, 'current_state_name' => $current_state_name ];
    }

    protected function buildCommand(array $command_values, AggregateRootInterface $aggregate_root)
    {
        $result = (new AggregateRootCommandBuilder($aggregate_root->getType(), $this->getCommandImplementor()))
            ->fromEntity($aggregate_root)
            ->withEventName($command_values['event'])
            ->withCurrentStateName($command_values['current_state_name'])
            ->build();

        return $this->validateBuildResult($result);
    }
}
