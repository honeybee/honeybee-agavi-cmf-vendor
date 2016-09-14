<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Task\Proceed;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

abstract class ProceedAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $resource = $request_data->getParameter('resource');
        $workflow_service = $this->getServiceLocator()->getWorkflowService();
        $state_machine = $workflow_service->getStateMachine($resource);
        $this->setAttribute('resource', $resource);
        $this->setAttribute('resource_type', $resource->getType());
        $this->setAttribute('current_state', $resource->getWorkflowState());
        $this->setAttribute('view_scope', $this->getScopeKey());
        $this->setAttribute(
            'supported_events',
            $workflow_service->getSupportedEventsFor($state_machine, $resource->getWorkflowState(), true)
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
