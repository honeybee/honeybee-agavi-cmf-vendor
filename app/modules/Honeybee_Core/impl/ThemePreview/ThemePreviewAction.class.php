<?php

use Honeygavi\Agavi\App\Base\Action;

class Honeybee_Core_ThemePreviewAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Success';
    }

    /**
     * @return boolean
     */
    public function isSecure()
    {
        return true;
    }

}
