<?php

use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

class Honeybee_Core_System_RelsAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('activity', $request_data->getParameter('activity'));

        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Error';
    }

    /**
     * @return boolean
     */
    public function isSecure()
    {
        return true;
    }

}
