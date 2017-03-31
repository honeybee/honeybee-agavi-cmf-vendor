<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_Core_Migrate_Create_CreateErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> failure creating a new migration';

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getErrorMessages() as $error) {
                $message .= sprintf(' - %s' . PHP_EOL, $error);
            }
        }

        return $this->cliError($message);
    }
}
