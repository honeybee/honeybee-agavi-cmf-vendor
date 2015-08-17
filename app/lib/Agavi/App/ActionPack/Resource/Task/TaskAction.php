<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Task;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Projection\WorkflowSubject;

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
        $state_machine = $resource->getType()->getWorkflowStateMachine();

        $this->setAttribute(
            'task_info',
            WorkflowSubject::getTaskByStateAndEvent($state_machine, $resource, $request_data->getParameter('event'))
        );
    }
}
