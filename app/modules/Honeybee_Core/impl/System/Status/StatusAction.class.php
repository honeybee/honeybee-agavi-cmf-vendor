<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

class Honeybee_Core_System_StatusAction extends Action
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
