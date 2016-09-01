<?php

use Honeybee\Infrastructure\Security\Auth\AuthResponse;
use Honeybee\Infrastructure\Security\Acl\AclService;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

class Honeybee_SystemAccount_User_LoginAction extends Action
{
    /**
     * Execute our read logic, hence get the login prompt up.
     *
     * @param AgaviParameterHolder $request_data
     *
     * @return string The name of the view to execute.
     */
    public function executeRead(AgaviParameterHolder $request_data)
    {
        $this->setAttribute('reset_support_enabled', AgaviConfig::get('user.module_active', false));
        // @todo create a new validator isCliEnvironmentValidator, that gives a more reliable acknowledgement
        // on whether we are being called from the shell or not. Here would then ask the ValidationManager,
        // if the latter validator has successfully executed.
        if ('console' === $this->getContext()->getName()) {
            return $this->authenticate($request_data);
        } else {
            return 'Input';
        }
    }

    /**
     * Try to login based on the account information, that is provided with our given $request_data.
     *
     * @param AgaviParameterHolder $request_data
     *
     * @return string The name of the view to execute.
     */
    public function executeWrite(AgaviParameterHolder $request_data)
    {
        $this->setAttribute('reset_support_enabled', AgaviConfig::get('user.module_active', false));

        return $this->authenticate($request_data);
    }

    /**
     * This method handles validation errors that occur upon our received input data.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string The name of the view to execute.
     */
    public function handleError(AgaviRequestDataHolder $request_data)
    {
        $validation_manager = $this->getContainer()->getValidationManager();

        $this->logError(
            "[UNAUTHORIZED] Failed authentication attempt for username '",
            $request_data->getParameter('username'),
            "' - validation failed:",
            $validation_manager
        );

        $errors = [];
        foreach ($validation_manager->getErrors() as $field => $error) {
            $errors[$field] = $error['messages'][0];
        }

        $errors['auth'] = $this->getContext()->getTranslationManager()->_(
            'invalid_login',
            'honeybee.system_account.user.errors'
        );

        $this->setAttribute('errors', $errors);
        $this->setAttribute('reset_support_enabled', AgaviConfig::get('user.module_active', false));

        return 'Error';
    }

    /**
     * Tries to authenticate the user with the given request data.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string name of agavi view to select
     */
    protected function authenticate(AgaviRequestDataHolder $request_data)
    {
        $vm = $this->getContainer()->getValidationManager();
        $tm = $this->getContext()->getTranslationManager();
        $user = $this->getContext()->getUser();

        $username = $request_data->getParameter('username');
        $password = $request_data->getParameter('password');

        $service_locator = $this->getContext()->getServiceLocator();
        $authentication_service = $service_locator->getAuthenticationService();

        $auth_response = $authentication_service->authenticate($username, $password);

        $log_message = sprintf(
            "username='%s' message='%s' auth_provider='%s' errors=''",
            $username,
            $auth_response->getMessage(),
            get_class($authentication_service),
            join(';', $auth_response->getErrors())
        );

        if ($auth_response->getState() === AuthResponse::STATE_AUTHORIZED) {
            $view_name = 'Success';

            $user->setAttributes(
                array_merge(
                    [ 'acl_role' => AclService::ROLE_NON_PRIV ],
                    $auth_response->getAttributes()
                )
            );
            $user->setAuthenticated(true);

            $this->logInfo('[AUTHORIZED] ' . $log_message);
        } elseif ($auth_response->getState() === AuthResponse::STATE_UNAUTHORIZED) {
            $view_name = 'Error';

            $user->setAuthenticated(false);

            $vm->addArgumentResult(new \AgaviValidationArgument('username'), AgaviValidator::ERROR);
            $vm->addArgumentResult(new \AgaviValidationArgument('password'), AgaviValidator::ERROR);
            $vm->setError('invalid_login', $tm->_('invalid_login', 'honeybee.system_account.user.errors'));

            $this->logError('[UNAUTHORIZED] ' . $log_message);
        } else {
            $view_name = 'Error';

            $user->setAuthenticated(false);

            $this->setAttribute('errors', [ 'auth' => $auth_response->getMessage() ]);
            $vm->setError('invalid_login', $tm->_('invalid_login', 'honeybee.system_account.user.errors'));

            $this->logError("[ERROR] state='" . $auth_response->getState() . "' " . $log_message);
        }

        return $view_name;
    }

    /**
     * Return whether this action requires authentication before execution.
     *
     * @return boolean false as login is not required for login attempts.
     */
    public function isSecure()
    {
        return false;
    }

    /**
     * @return string 'auth' as the default logger to use for `log<Level>()` calls
     */
    public function getLoggerName()
    {
        return 'auth';
    }
}
