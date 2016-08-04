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

    public function handleWriteError(AgaviRequestDataHolder $parameters)
    {
        $errors = [];
        foreach ($this->getContainer()->getValidationManager()->getErrorMessages() as $error_message) {
            $errors[] = implode(', ', $error_message['errors']) . ': ' . $error_message['message'];
        }

        $this->setAttribute('errors', $errors);

        return 'Input';
    }

    public function isSecure()
    {
        return false;
    }
}
