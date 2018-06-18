<?php

namespace Honeygavi\App\ActionPack\Resource\Task;

use AgaviRequestDataHolder;
use AgaviWebResponse;
use Honeygavi\App\Base\ErrorView;

class TaskErrorView extends ErrorView
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
        $this->setHttpStatusCode();
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setContent(
            json_encode(
                array(
                    'state' => 'ok',
                    'messages' => $this->getAttribute('errors')
                ),
                self::JSON_OPTIONS
            )
        );
        $this->setHttpStatusCode();
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliError(
            'Errors:' . PHP_EOL . implode(PHP_EOL, $this->getAttribute('errors', array()))
        );
    }

    protected function setHttpStatusCode()
    {
        $response = $this->getResponse();
        if ($response instanceof AgaviWebResponse) {
            $response->setHttpStatusCode($this->getHttpStatusCode());
        }
    }

    protected function getHttpStatusCode()
    {
        $validation_manager = $this->getContainer()->getValidationManager();
        if ($validation_manager) {
            foreach ($validation_manager->getIncidents() as $incident) {
                foreach ($incident->getErrors() as $error) {
                    switch ($error->getName()) {
                        case 'non_existent':
                            return 404;   // resource not found
                        case 'resource_permissions':
                            return 403;   // forbidden
                        case 'missing_workflow_event':
                        case 'invalid_workflow_event':
                        case 'workflow_event_not_supported':
                            return 409;   // unprocessable entity
                        case 'no_transition_supported':
                            return 410;   // gone
                        default:
                            # default 5xx
                            return 500;
                    }
                }
            }
        }

        return 400;
    }
}
