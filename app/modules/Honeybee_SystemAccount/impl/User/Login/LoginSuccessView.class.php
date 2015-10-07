<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Trellis\Common\Error\RuntimeException;

class Honeybee_SystemAccount_User_Login_LoginSuccessView extends View
{
    const GRAVATAR_TEMPLATE = '//www.gravatar.com/avatar/{MD5_HASH}?';

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeHtml(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        if (!session_regenerate_id(true)) {
            $error_message = "[SESSIONID_REGENERATION_FAILED] SessionId could not be regenerated.";
            $this->logFatal($error_message);

            throw new RuntimeException($error_message);
        }

        // set the user avatar url
        $user = $this->getContext()->getUser();
        $user->setAttributes([
            'avatar_url' => $this->generateAvatarUrl()
        ]);

        $default_target_url = $this->routing->gen('index');  // dashboard a.k.a. homepage

        // login after input view - redirect to previous original target or referring URL
        if ($this->user->hasAttribute('redirect_url', 'de.honeybee-cms.login')) {
            $url = $this->user->removeAttribute('redirect_url', 'de.honeybee-cms.login', $default_target_url);
            $this->setRedirect($url);
            return;
        }

        // login via internal forward - forward back to originally requested action
        if ($this->container->hasAttributeNamespace('org.agavi.controller.forwards.login')) {
            $container = null;
            $agavi_login_namespace = 'org.agavi.controller.forwards.login';
            $requested_module = $this->container->getAttribute('requested_module', $agavi_login_namespace);
            $requested_action = $this->container->getAttribute('requested_action', $agavi_login_namespace);

            if (!empty($requested_module) && !empty($requested_action)) {
                $container = $this->createForwardContainer($requested_module, $requested_action);
            }

            if (null !== $container) {
                return $container;
            }
        }

        // normal login via login form - no success template, but direct redirect to dashboard
        $this->setRedirect($default_target_url);
    }

    public function executeJson(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        if (!session_regenerate_id(true)) {
            $error_message = "[SESSIONID_REGENERATION_FAILED] SessionId could not be regenerated.";
            $this->logFatal($error_message);

            throw new RuntimeException($error_message);
        }

        $default_target_url = $this->routing->gen('index');  // dashboard a.k.a. homepage

        // login after input view - redirect to previous original target or referring URL
        if ($this->user->hasAttribute('redirect_url', 'de.honeybee-cms.login')) {
            $url = $this->user->removeAttribute('redirect_url', 'de.honeybee-cms.login', $default_target_url);
            $this->setRedirect($url);
            return;
        }

        // login via internal forward - forward back to originally requested action
        if ($this->container->hasAttributeNamespace('org.agavi.controller.forwards.login')) {
            $container = null;
            $agavi_login_namespace = 'org.agavi.controller.forwards.login';
            $requested_module = $this->container->getAttribute('requested_module', $agavi_login_namespace);
            $requested_action = $this->container->getAttribute('requested_action', $agavi_login_namespace);

            if (!empty($requested_module) && !empty($requested_action)) {
                $container = $this->createForwardContainer($requested_module, $requested_action);
            }

            if (null !== $container) {
                return $container;
            }
        }

        // normal login via login form - no success template, but direct redirect to dashboard
        $this->setRedirect($default_target_url);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeConsole(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        if ($this->container->hasAttributeNamespace('org.agavi.controller.forwards.login')) {
            $container = null;
            $agavi_login_namespace = 'org.agavi.controller.forwards.login';
            $requested_module = $this->container->getAttribute('requested_module', $agavi_login_namespace);
            $requested_action = $this->container->getAttribute('requested_action', $agavi_login_namespace);

            if (!empty($requested_module) && !empty($requested_action)) {
                $container = $this->createForwardContainer($requested_module, $requested_action);
            }

            if (null !== $container) {
                return $container;
            }
        }

        $error_message = "[MISSING_FORWARD_TARGET] No internal forward container found.";
        $this->logFatal($error_message);

        throw new RuntimeException($error_message);
    }

    protected function setRedirect($url)
    {
        if ($this->request->getProtocol() === 'HTTP/1.1') {
            $this->getResponse()->setRedirect($url, 303);
        } else {
            $this->getResponse()->setRedirect($url, 302);
        }
    }

    public function generateAvatarUrl()
    {
        if ($this->user->hasAttribute('avatar')) {
            $user_avatar_url = $this->user->getAttribute('avatar');
        } else {
            // generate Gravatar url
            $gravatar_template = AgaviConfig::get('core.gravatar_template', self::GRAVATAR_TEMPLATE);

            $user_email = trim($this->user->getAttribute('email'));
            $user_email_md5 = md5(strtolower($user_email));
            $user_avatar_url = str_replace('{MD5_HASH}', $user_email_md5, $gravatar_template);

            // @todo add additional gravatar options as 'size' and 'default'
        }

        return $user_avatar_url;
    }
}
