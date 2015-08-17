<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Common\Error\RuntimeError;
use \AgaviView;

class Honeybee_SystemAccount_User_ApiLogin_ApiLoginSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        return $this->forwardToRequestedAction();
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return $this->forwardToRequestedAction();
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        return $this->forwardToRequestedAction();
    }

    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        return $this->forwardToRequestedAction();
    }

    protected function forwardToRequestedAction()
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

        throw new RuntimeError($error_message);
    }
}
