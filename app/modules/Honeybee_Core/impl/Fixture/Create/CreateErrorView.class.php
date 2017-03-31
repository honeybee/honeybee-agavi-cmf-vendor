<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Fixture_Create_CreateErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> failure creating a new fixture';

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getErrorMessages() as $error) {
                $message .= sprintf(PHP_EOL . ' - %s', $error);
            }
        }

        return $this->cliError($message);
    }
}
