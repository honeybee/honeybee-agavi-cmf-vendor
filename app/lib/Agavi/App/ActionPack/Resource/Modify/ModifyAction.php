<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Modify;

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use AgaviRequestDataHolder;

class ModifyAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $resource = $request_data->getParameter('resource');
        $this->setAttribute('resource', $resource);
        $this->setAttribute('view_scope', $this->getScopeKey());

        return 'Input';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('command', $this->dispatchCommand($request_data->getParameter('command')));

        return 'Success';
    }

    public function handleWriteError(AgaviRequestDataHolder $request_data)
    {
        parent::handleError($request_data);

        $resource = $request_data->getParameter('resource');
        $task_service = $this->getServiceLocator()->getTaskService();

        if ($task_service->hasTaskConflicts()) {
            $task_conflict = $task_service->getLastTaskConflict();
            $this->setAttribute('task_conflict', $task_conflict);
            $this->setAttribute('resource', $task_conflict->getCurrentResource());
            $this->setAttribute('view_scope', $this->getScopeKey());
            return 'Error';
        }

        return $resource ? $this->executeRead($request_data) : 'Error';
    }
}
