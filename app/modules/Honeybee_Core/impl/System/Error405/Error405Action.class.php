<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

/**
 * Handles the HTTP status code 405 METHOD NOT ALLOWED logic.
 */
class Honeybee_Core_System_Error405Action extends Action
{
    public function getDefaultViewName()
    {
        error_log(__METHOD__);
        return 'Success';
    }

    public function executeRead(AgaviRequestDataHolder $rd)
    {
        error_log(__METHOD__);
        return 'Success';
    }

    public function executeRemove(AgaviRequestDataHolder $rd)
    {
        error_log(__METHOD__);
        return 'Success';
    }

    public function executeDelete(AgaviRequestDataHolder $rd)
    {
        error_log(__METHOD__);
        return 'Success';
    }

    public function execute(AgaviRequestDataHolder $rd)
    {
        error_log(__METHOD__);
        return 'Success';
    }

    public function handleError(AgaviRequestDataHolder $rd)
    {
        error_log(__METHOD__);
        return 'Success';
    }

    public function isSimple()
    {
        error_log(__METHOD__);
        return true;
    }

    public function isSecure()
    {
        error_log(__METHOD__);
        return false;
    }
}
