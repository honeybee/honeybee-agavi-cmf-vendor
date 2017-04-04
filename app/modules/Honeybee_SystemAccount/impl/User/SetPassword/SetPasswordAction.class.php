<?php

use Honeygavi\App\Base\Action;

class Honeybee_SystemAccount_User_SetPasswordAction extends Action
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

    public function handleError(AgaviRequestDataHolder $request_data)
    {
        parent::handleError($request_data);

        $errors = array();
        $default_view = 'Input';

        $validation_manager = $this->getContainer()->getValidationManager();
        foreach ($validation_manager->getErrorMessages() as $error_info) {
            if (array_key_exists('errors', $error_info)
                && is_array($error_info['errors'])
                && reset($error_info['errors']) === 'token'
            ) {
                // If an invalid token was provided, escalate to the error template
                $default_view = 'Error';
            }
            $errors[] = $error_info['message'];
        }
        $this->setAttribute('errors', $errors);

        return $default_view;
    }
}
