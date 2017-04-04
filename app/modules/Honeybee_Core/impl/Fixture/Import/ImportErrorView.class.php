<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Fixture_Import_ImportErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> failure importing data from fixture "%s"',
            $request_data->getParameter('fixture')
        );

        if (!$request_data->getParameter('quiet')) {
            $message .= PHP_EOL . ' - ' . $this->getAttribute('errors');
        }

        return $this->cliError($message);
    }
}
