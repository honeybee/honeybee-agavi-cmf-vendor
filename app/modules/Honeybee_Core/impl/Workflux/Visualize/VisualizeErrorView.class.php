<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Workflux_Visualize_VisualizeErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> failure generating visualization';

        if (!$request_data->getParameter('quiet')) {
            $message .=  PHP_EOL . ' - ' . implode(PHP_EOL, $this->getAttribute('errors'));
        }

        return $this->cliError($message);
    }
}
