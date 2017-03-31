<?php

namespace Honeygavi\Agavi\App\ActionPack\Resource\Task;

use AgaviRequestDataHolder;
use Honeygavi\Agavi\App\Base\Action;

class TaskAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->setTaskInfo($request_data);

        return 'Success';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $this->setTaskInfo($request_data);

        return 'Success';
    }

    protected function setTaskInfo(AgaviRequestDataHolder $request_data)
    {
        $resource = $request_data->getParameter('resource');
        $workflow_service = $this->getServiceLocator()->getWorkflowService();
        $state_machine = $workflow_service->getStateMachine($resource);

        $this->setAttribute(
            'task_info',
            $workflow_service->getTaskByStateAndEvent($state_machine, $resource, $request_data->getParameter('event'))
        );
    }
}
