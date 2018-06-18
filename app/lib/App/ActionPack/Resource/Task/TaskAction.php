<?php

namespace Honeygavi\App\ActionPack\Resource\Task;

use AgaviRequestDataHolder;
use AgaviValidator;
use Honeygavi\App\Base\Action;
use Honeygavi\Validator\WorkflowEventValidator;

class TaskAction extends Action
{
    const WORKFLOW_EVENT_VALIDATOR_NAME = 'workflow_event_not_supported';

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

    public function validate(AgaviRequestDataHolder $request_data)
    {
        if (!$request_data->getParameter('resource')) {
            return false;
        }

        if (AgaviValidator::SILENT < $this->getWorkflowEventValidator()->execute($request_data)) {
            return false;
        }
        return true;
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

    protected function getWorkflowEventValidator()
    {
        $parameters = [
            'required' => true,
            'name' => static::WORKFLOW_EVENT_VALIDATOR_NAME
        ];
        $arguments = [
            'resource' => 'resource',
            'workflow_event' => 'event'
        ];
        $errors = [
            'missing_workflow_event' => 'A valid workflow event should be provided',
            'no_transition_supported' => 'No transitions available in the current state.',
            static::WORKFLOW_EVENT_VALIDATOR_NAME => 'Current resource state doesn\'t support the provided event.',
        ];
        $validator = new WorkflowEventValidator();
        $validator->initialize($this->getContext(), $parameters, $arguments, $errors);
        $validator->setParentContainer($this->getContainer()->getValidationManager());

        return $validator;
    }
}
