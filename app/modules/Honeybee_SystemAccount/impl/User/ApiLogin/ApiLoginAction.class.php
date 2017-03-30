<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeygavi\Security\Auth\AuthResponse;
use Honeygavi\Security\Acl\AclService;

class Honeybee_SystemAccount_User_ApiLoginAction extends Action
{
    public function execute(AgaviParameterHolder $request_data)
    {
        return $this->authenticate($request_data);
    }

    public function getDefaultViewName()
    {
        return 'Error';
    }

    /**
     * Handles validation errors that occur upon received request data.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string The name of the view to execute.
     */
    public function handleError(AgaviRequestDataHolder $request_data)
    {
        $this->logError(
            '[UNAUTHORIZED] Failed authentication attempt: ',
            $this->getContainer()->getValidationManager()
        );

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
        $translation_manager = $this->getContext()->getTranslationManager();
        $user = $this->getContext()->getUser();

        $auth_request = $request_data->getHeader('AUTH_REQUEST');

        $username = $auth_request['username'];
        $password = $auth_request['password'];

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

        $log_message_part = sprintf("for username '$username' via auth provider %s.", get_class($authentication_service));

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

            $this->setAttribute('errors', [
                'auth' => $translation_manager->_('invalid_login', 'honeybee.system_account.user.errors')
            ]);

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
