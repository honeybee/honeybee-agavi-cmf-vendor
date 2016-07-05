<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandBuilder;
use Honeybee\Projection\WorkflowSubject;

class ProceedWorkflowCommandValidator extends AggregateRootCommandValidator
{
    protected function getCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        // no need to filter payload as we are not building the command with values
        return $request_payload;
    }

    protected function buildCommand(array $command_values, AggregateRootInterface $aggregate_root)
    {
        $argument = $this->getArgument();
        if (!isset($command_values[$argument])) {
            $this->throwError('missing_workflow_event');
            return false;
        }

        $state_machine = $aggregate_root->getType()->getWorkflowStateMachine();
        $current_state_name = $aggregate_root->getWorkflowState();
        $supported_events = WorkflowSubject::getSupportedEventsFor($state_machine, $current_state_name);

        $event_name = $command_values[$argument];
        if (!in_array($event_name, $supported_events)) {
            $this->throwError('invalid_workflow_event');
            return false;
        }

        $result = (new AggregateRootCommandBuilder($aggregate_root->getType(), $this->getCommandImplementor()))
            ->fromEntity($aggregate_root)
            ->withEventName($event_name)
            ->withCurrentStateName($current_state_name)
            ->build();

        return $this->validateBuildResult($result);
    }
}
