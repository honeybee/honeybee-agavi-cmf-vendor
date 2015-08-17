<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

class Honeybee_SystemAccount_User_ResetPasswordAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        return 'Input';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('command', $this->dispatchCommand($request_data->getParameter('command')));

        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
