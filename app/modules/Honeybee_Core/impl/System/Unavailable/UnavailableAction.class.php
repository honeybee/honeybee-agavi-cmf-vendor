<?php

use Honeygavi\Agavi\App\Base\Action;

/**
 * Handles the %system_actions.unavailable% logic (in case of maintenance).
 */
class Honeybee_Core_System_UnavailableAction extends Action
{
    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
