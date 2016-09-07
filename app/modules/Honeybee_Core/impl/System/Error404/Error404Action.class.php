<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

/**
 * Handles the %system_actions.error_404% logic.
 */
class Honeybee_Core_System_Error404Action extends Action
{
    public function executeRead(AgaviRequestDataHolder $rd)
    {
        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
