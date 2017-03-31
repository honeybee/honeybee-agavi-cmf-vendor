<?php

use \AgaviRequestDataHolder;
use \AgaviWebResponse;
use Honeygavi\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_ApiLogin_ApiLoginErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $this->setAttribute('_title', $this->translation_manager->_('Login Error', 'honeybee.system_account.user'));
        $this->setAttribute('error_messages', $this->getContainer()->getValidationManager()->getErrorMessages());

        // OPTIONS requests are most likely PREFLIGHT requests from CORS supporting browsers and those
        // browsers don't like getting 401 on OPTIONS requests with "Access-Control-Request-Headers:authorization"
        if ($this->request->getMethod() !== 'options') {
            $this->getResponse()->setHttpStatusCode(401);
            $this->getResponse()->setHttpHeader('WWW-Authenticate', 'Basic realm="api"');
        }
    }

    public function executeHaljson(AgaviRequestDataHolder $request_data)
    {
        if ($this->request->getMethod() !== 'options') {
            $this->getResponse()->setHttpStatusCode(401);
            $this->getResponse()->setHttpHeader('WWW-Authenticate', 'Basic realm="api"');
        }

        return json_encode([
            'message' => $this->translation_manager->_('Authentication needed.', 'honeybee.system_account.user.errors'),
            'errors' => $this->getContainer()->getValidationManager()->getErrorMessages()
        ]);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        if ($this->request->getMethod() !== 'options') {
            $this->getResponse()->setHttpStatusCode(401);
            $this->getResponse()->setHttpHeader('WWW-Authenticate', 'Basic realm="api"');
        }

        return json_encode([
            'message' => $this->translation_manager->_('Authentication needed.', 'honeybee.system_account.user.errors'),
            'errors' => $this->getContainer()->getValidationManager()->getErrorMessages()
        ]);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliError(
            $this->translation_manager->_('Login Error', 'honeybee.system_account.user.errors') . PHP_EOL
        );
    }

    /**
     * Handles non-existing methods. This includes mainly the not implemented
     * handling of certain output types. This returns HTTP status code 401 by default.
     *
     * @param string $method_name
     * @param array $arguments
     */
    public function __call($method_name, $arguments)
    {
        if (preg_match('~^(execute)([A-Za-z_]+)$~', $method_name)) {
            if ($this->getResponse() instanceof AgaviWebResponse) {
                if ($this->request->getMethod() !== 'options') {
                    $this->getResponse()->setHttpStatusCode(401);
                    $this->getResponse()->setHttpHeader('WWW-Authenticate', 'Basic realm="api"');
                }
            } elseif ($this->getResponse() instanceof AgaviConsoleResponse) {
                $this->getResponse()->setExitCode(70); // 70 ("internal software error") instead of 1 ("general error")
            }
        }
    }
}
