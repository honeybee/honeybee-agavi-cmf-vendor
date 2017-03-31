<?php

use Honeygavi\App\Base\Action;

/**
 * Handles the HTTP status code 501 NOT IMPLEMENTED logic.
 */
class Honeybee_Core_System_Error501Action extends Action
{
    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSimple()
    {
        return true;
    }

    public function isSecure()
    {
        return false;
    }
}
