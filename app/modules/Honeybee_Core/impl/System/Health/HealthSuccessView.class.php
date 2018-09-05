<?php

use Honeygavi\App\Base\View;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

class Honeybee_Core_System_Health_HealthSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $status = $this->getAttribute('status', 'ERROR');

        if ($status === Status::FAILING || $status === 'ERROR') {
            return $this->cliError($status);
        }

        return $this->cliMessage($status);
    }

    /**
     * Handles all the output types.
     *
     * @param string $method_name
     * @param array $arguments
     */
    public function __call($method_name, $arguments)
    {
        if (preg_match('~^(execute)([A-Za-z_]+)$~', $method_name)) {
            if ($this->getResponse() instanceof AgaviWebResponse) {
                $this->getResponse()->setContentType('text/plain');
                $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
            }

            $status = $this->getAttribute('status', 'ERROR');
            if ($status === Status::FAILING || $status === 'ERROR') {
                if ($this->getResponse() instanceof AgaviWebResponse) {
                    $this->getResponse()->setHttpStatusCode('500');
                } elseif ($this->getResponse() instanceof AgaviConsoleResponse) {
                    $this->getResponse()->setExitCode(1);
                }
                return Status::FAILING;
            } elseif ($status === Status::WORKING) {
                return Status::WORKING;
            }

            return $status; // Status::UNKNOWN
        }
    }
}
