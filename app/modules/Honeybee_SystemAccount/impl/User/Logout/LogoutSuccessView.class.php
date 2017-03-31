<?php

use Honeygavi\App\Base\View;

class Honeybee_SystemAccount_User_Logout_LogoutSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(self::ATTRIBUTE_RENDERED_NAVIGATION, '');
        $this->setupHtml($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getContainer()->getResponse()->setContent(
            json_encode(
                array(
                    'result'  => 'success',
                    'message' => $this->translation_manager->_('logout_success', 'honeybee.system_account.user.ui')
                )
            )
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliMessage(
            $this->translation_manager->_('logout_success', 'honeybee.system_account.user.ui') . PHP_EOL
        );
    }
}
