<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Projection\WorkflowSubject;

class ProceedWorkflowCommandValidator extends AggregateRootCommandValidator
{
    protected function getValidatedAggregateRootCommandPayload(AggregateRootInterface $aggregate_root)
    {
        $state_machine = $this->aggregate_root_type->getWorkflowStateMachine();
        $current_state_name = $aggregate_root->getWorkflowState();
        $supported_events = WorkflowSubject::getSupportedEventsFor($state_machine, $current_state_name);
        $event_name = $this->getData($this->getArgument());

        if (!in_array($event_name, $supported_events)) {
            $this->throwError('invalid_workflow_event');
            return [ 'success' => false, 'payload' => [] ];
        }

        return [ 'current_state_name' => $current_state_name, 'event_name' => $event_name ];
    }
}
