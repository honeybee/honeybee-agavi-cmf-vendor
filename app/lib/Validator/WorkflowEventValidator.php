<?php

namespace Honeygavi\Validator;

use AgaviValidator;
use Workflux\Error\Error;

class WorkflowEventValidator extends AgaviValidator
{
    protected function validate()
    {
        $event_argument = $this->getArgument('workflow_event');
        $workflow_event = $this->getData($event_argument);
        if (!$workflow_event) {
            $this->throwError('missing_workflow_event');
            return false;
        }

        $resource = $this->getData($this->getArgument('resource')); // Honeybee\Projection\ProjectionInterface
        $workflow_service = $this->getContext()->getServiceLocator()->getWorkflowService();
        $state_machine = $workflow_service->getStateMachine($resource);
        $current_state = $resource->getWorkflowState();
        // error is thrown instead of returning empty when there are no supported events for the current-state.
        try {
            $supported_events = $workflow_service->getSupportedEventsFor($state_machine, $current_state);
        } catch (Error $e) {
            $this->throwError('no_transition_supported', $event_argument);
            return false;
        }

        if (!is_string($workflow_event) || !in_array($workflow_event, $supported_events)) {
            $this->throwError('workflow_event_not_supported', $event_argument);
            return false;
        }

        $this->export($workflow_event, $this->getParameter('export', $event_argument));

        return true;
    }
}
