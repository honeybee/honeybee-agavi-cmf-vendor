<?php

use Honeybee\Common\Error\RuntimeError;
use Honeygavi\App\Base\View;

class Honeybee_SystemAccount_User_ApiLogin_ApiLoginSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        return $this->forwardToRequestedAction();
    }

    public function executeHaljson(AgaviRequestDataHolder $request_data)
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

    /**
     * Handles non-existing methods. This includes mainly the not implemented
     * handling of certain output types.
     *
     * @param string $method_name
     * @param array $arguments
     *
     * @throws AgaviViewException with different messages
     */
    public function __call($method_name, $arguments)
    {
        if (preg_match('~^(execute)([A-Za-z_]*)$~', $method_name, $matches)) {
            return $this->forwardToRequestedAction();
        }

        throw new AgaviViewException(
            sprintf(
                'The view "%1$s" does not implement an "%2$s()" method. Please ' .
                'implement "%1$s::%2$s()" or handle this situation in one of the base views (e.g. "%3$s").',
                get_class($this),
                $method_name,
                get_class()
            )
        );
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
        $this->logError($error_message);

        throw new RuntimeError($error_message);
    }
}
