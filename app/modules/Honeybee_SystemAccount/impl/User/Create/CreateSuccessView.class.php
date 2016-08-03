<?php

use Honeybee\FrameworkBinding\Agavi\App\ActionPack\Create\CreateSuccessView;

class Honeybee_SystemAccount_User_Create_CreateSuccessView extends CreateSuccessView
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $command = $request_data->getParameter('command');
        $values = $command->getValues();

        // this should be more sophisticated as it e.g. doesn't include a custom port
        $set_password_url = sprintf(
            '%shoneybee-system_account-user/password?token=%s',
            AgaviConfig::get('local.base_href'),
            $values['auth_token']
        );

        $set_password_cli_command = $this->routing->gen('honeybee.system_account.user.password', ['token' => $values['auth_token']]);

        $this->cliMessage(
            PHP_EOL .
            $this->translation_manager->_('Please set a password for the created account at: ') . $set_password_url .
            PHP_EOL .
            $this->translation_manager->_('Via CLI use the following: ') . $set_password_cli_command .
            PHP_EOL
        );
    }
}
