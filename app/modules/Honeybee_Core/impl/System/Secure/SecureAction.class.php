<?php

use Honeygavi\Agavi\App\Base\Action;

/**
 * Handles the %system_actions.secure% action logic which is executed by the
 * \AgaviSecurityFilter as soon as an action that is marked as secure is
 * encountered without the having an authenticated user session.
 */
class Honeybee_Core_System_SecureAction extends Action
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
