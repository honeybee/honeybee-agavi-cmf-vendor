<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Migrate_List_ListErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> failure trying to list migrations' . PHP_EOL;

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getErrorMessages() as $error) {
                $message .= sprintf(' - %s' . PHP_EOL, $error);
            }
        }

        return $this->cliError($message);
    }
}
