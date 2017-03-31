<?php

use Honeygavi\Agavi\App\Base\Action;

class Honeybee_Core_IndexAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $rd)
    {
        return 'Success';
    }

    /**
     * This method returns the View name in case the Action doesn't serve the
     * current Request method.
     *
     * !!!!!!!!!! DO NOT PUT ANY LOGIC INTO THIS METHOD !!!!!!!!!!
     *
     * @return     mixed - A string containing the view name associated with this
     *                     action, or...
     *                   - An array with two indices:
     *                     0. The parent module of the view that will be executed.
     *                     1. The view that will be executed.
     *
     */
    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSecure()
    {
        return true;
    }
}
