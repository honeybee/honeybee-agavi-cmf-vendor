<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_System_Health_HealthErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        return $this->cliError('Health check failed');
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
                $this->getResponse()->setHttpStatusCode(500);
            } elseif ($this->getResponse() instanceof AgaviConsoleResponse) {
                $this->getResponse()->setExitCode(1);
            }

            return 'Health check failed.';
        }
    }
}
