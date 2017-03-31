<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_SetPassword_SetPasswordSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(self::ATTRIBUTE_RENDERED_NAVIGATION, '');
        $this->setupHtml($request_data);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliMessage(
            $this->translation_manager->_('Set Password - Success', 'honeybee.system_account.user.ui')
        );
    }
}
