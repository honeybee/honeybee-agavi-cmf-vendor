<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_Login_LoginErrorView extends View
{
    /**
     * Execute any html related presentation logic and sets up our template attributes.
     *
     * @param AgaviRequestDataHolder $request_data
     */
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(self::ATTRIBUTE_RENDERED_NAVIGATION, '');
        $this->setupHtml($request_data);

        $this->setAttribute('reset_password_enabled', AgaviConfig::get('core.reset_password_enabled', true));

        $this->getResponse()->setHttpStatusCode(401);

        // allow users to log in directly via html form
        $this->getLayer('content')->setTemplate('User/Login/LoginInput');
    }

    /**
     * Prepares and sets our json data on our webresponse.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeJson(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->getResponse()->setHttpStatusCode(401);

        return json_encode(
            array(
                'result' => 'failure',
                'errors' => $this->getContainer()->getValidationManager()->getErrorMessages()
            )
        );
    }

    /**
     * Prepares and sets our json data on our console response.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeConsole(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        return $this->cliError(
            $this->translation_manager->_('Wrong username or password!', 'honeybee.system_account.user.errors')
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
                $this->getResponse()->setHttpStatusCode(401);
            } elseif ($this->getResponse() instanceof AgaviConsoleResponse) {
                $this->getResponse()->setExitCode(70); // 70 ("internal software error") instead of 1 ("general error")
            }
        }
    }
}
