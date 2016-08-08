<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_SystemAccount_User_Login_LoginInputView extends View
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

        if ($this->container->hasAttributeNamespace('org.agavi.controller.forwards.login')) {
            // forward from controller due to secure action (and login action could not authenticate automatically)
            // store the input URL in the session for a redirect after login
            $base_href = $this->routing->getBaseHref();
            $url = $this->request->getUrl();
            // we only want to store the requested URL when it starts with the current base href
            if (strpos($url, "$base_href", 0) === 0) {
                // only store URL when it was a GET as otherwise the URL may not even have a read method
                if ($this->request->getMethod() !== 'read') {
                    // we store the REFERER when the request is not GET, as it's most probably a form on that page.
                    // when no valid REFERER is available we use the target input URL instead
                    $url = $request_data->get('headers', 'REFERER', $url);
                }
                $this->user->setAttribute('redirect_url', $url, 'de.honeybee-cms.login');
            }
            // as this is an internal forward the input form will not be the expected output of users/consumers,
            // thus we need to tell them that they're not authenticated and must fill the form or fix it otherwise
            $this->getResponse()->setHttpStatusCode(401);
        } else {
            // clear redirect from session as it's probably just a direct request of this login form
            if ($this->request->getMethod() === 'read') {
                $this->user->removeAttribute('redirect_url', 'de.honeybee-cms.login');
            } else {
                // when users submit wrong credentials we don't want to forget his original target url
            }
        }

        $this->setAttribute('reset_password_enabled', AgaviConfig::get('core.reset_password_enabled', true));
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode(401);

        return json_encode([
            'result' => 'failure',
            'message' => $this->translation_manager->_('Authentication needed.', 'honeybee.system_account.user.errors')
        ]);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $error_message = $this->translation_manager->_(
            'Please provide username and password as commandline arguments when calling secure actions. ' .
            'Use -username {user} -password {pass}.',
            'honeybee.system_account.user.errors'
        ) . PHP_EOL;

        return $this->cliError($error_message);
    }

    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode(401);
        return '';
    }

}
