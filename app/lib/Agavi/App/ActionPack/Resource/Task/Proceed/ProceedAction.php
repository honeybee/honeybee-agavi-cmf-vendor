<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Task\Proceed;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Projection\WorkflowSubject;

abstract class ProceedAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $resource = $request_data->getParameter('resource');
        $state_machine = $resource->getType()->getWorkflowStateMachine();
        $this->setAttribute('resource', $resource);
        $this->setAttribute('resource_type', $resource->getType());
        $this->setAttribute('current_state', $resource->getWorkflowState());
        $this->setAttribute('view_scope', $this->getScopeKey());
        $this->setAttribute(
            'supported_events',
            WorkflowSubject::getSupportedEventsFor($state_machine, $resource->getWorkflowState(), true)
        );

        return 'Input';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $command = $request_data->getParameter('command');
        $this->setAttribute('command', $this->dispatchCommand($command));
        $this->setAttribute('resource_type', $this->getProjectionType());
        $this->setAttribute('start_state', $command->getCurrentStateName());
        $this->setAttribute('event_name', $command->getEventName());

        return 'Success';
    }
}
