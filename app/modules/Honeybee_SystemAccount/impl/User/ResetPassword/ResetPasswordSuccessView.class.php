<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_ResetPassword_ResetPasswordSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(self::ATTRIBUTE_RENDERED_NAVIGATION, '');
        $this->setupHtml($request_data);
    }
}
