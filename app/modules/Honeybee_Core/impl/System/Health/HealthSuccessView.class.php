<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

class Honeybee_Core_System_Health_HealthSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        if ($this->getAttribute('status') === Status::FAILING) {
            return $this->cliError(Status::FAILING);
        }

        return $this->cliMessage(Status::WORKING);
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

            if ($this->getAttribute('status') === Status::FAILING) {
                if ($this->getResponse() instanceof AgaviWebResponse) {
                    $this->getResponse()->setHttpStatusCode('500');
                } elseif ($this->getResponse() instanceof AgaviConsoleResponse) {
                    $this->getResponse()->setExitCode(1);
                }
                return Status::FAILING;
            }

            return Status::WORKING;
        }
    }
}
