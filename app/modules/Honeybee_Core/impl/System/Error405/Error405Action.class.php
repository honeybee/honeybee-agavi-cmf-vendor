<?php

use Honeygavi\App\Base\Action;

/**
 * Handles the HTTP status code 405 METHOD NOT ALLOWED logic.
 */
class Honeybee_Core_System_Error405Action extends Action
{
    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function executeRead(AgaviRequestDataHolder $rd)
    {
        return 'Success';
    }

    public function handleError(AgaviRequestDataHolder $rd)
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
