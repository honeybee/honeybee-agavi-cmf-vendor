<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_Logout_LogoutErrorView extends View
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
                    'result'  => 'error',
                    'message' => $this->translation_manager->_('An unexpected error occured during logout.', 'honeybee.system_account.user.ui')
                )
            )
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliError(
            $this->translation_manager->_("An unexpected error occured during logout.", 'honeybee.system_account.user.ui')
        );
    }
}
