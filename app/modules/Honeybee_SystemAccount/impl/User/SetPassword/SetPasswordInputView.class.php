<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_SetPassword_SetPasswordInputView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(self::ATTRIBUTE_RENDERED_NAVIGATION, '');
        $this->setupHtml($request_data);
        $this->setAttribute('token', $request_data->getParameter('token'));

        $this->setPasswordMeterOptions();
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliMessage('Errors: ' . implode(PHP_EOL . '- ', $this->getAttribute('errors')));
    }

    protected function setPasswordMeterOptions()
    {
        $password_requirements = array(
            'min_decimal_numbers' => (int)AgaviConfig::get('password_constraints.min_decimal_numbers', 2),
            'min_uppercase_chars' => (int)AgaviConfig::get('password_constraints.min_uppercase_chars', 2),
            'min_lowercase_chars' => (int)AgaviConfig::get('password_constraints.min_lowercase_chars', 2),
            'min_string_length' => (int)AgaviConfig::get('password_constraints.min_string_length', 10),
            'max_string_length' => (int)AgaviConfig::get('password_constraints.max_string_length', 32)
        );

        $password_meter_options = array(
            'requirements' => $password_requirements,
            'popover' => array(
                'pos' => 'right',
                'title' => 'Kennwortrichtlinien',
                'tpl_selector' => '#password_requirements'
            )
        );

        $this->setAttribute('password_meter_options', $password_meter_options);
    }
}
