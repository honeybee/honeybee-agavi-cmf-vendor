<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_Create_CreateErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliError(
            'Failed to create user, errors:' . PHP_EOL . implode(PHP_EOL, $this->getAttribute('errors', array()))
        );
    }
}
