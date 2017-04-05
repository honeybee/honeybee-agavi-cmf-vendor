<?php

use Honeygavi\App\Base\View;

class Honeybee_SystemAccount_User_SetPassword_SetPasswordErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(self::ATTRIBUTE_RENDERED_NAVIGATION, '');
        $this->setupHtml($request_data);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliMessage('Errors: ' . implode(PHP_EOL . '- ', $this->getAttribute('errors')));
    }
}
