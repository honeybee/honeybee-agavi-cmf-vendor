<?php

use Honeygavi\Agavi\App\Base\Action;

/**
 * Handles the %system_actions.module_disabled% logic.
 */
class Honeybee_Core_System_ModuleDisabledAction extends Action
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
