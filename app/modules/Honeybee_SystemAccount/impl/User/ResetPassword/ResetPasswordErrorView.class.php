<?php

use Honeygavi\App\Base\View;

class Honeybee_SystemAccount_User_ResetPassword_ResetPasswordErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(self::ATTRIBUTE_RENDERED_NAVIGATION, '');
        $this->setupHtml($request_data);
    }
}
