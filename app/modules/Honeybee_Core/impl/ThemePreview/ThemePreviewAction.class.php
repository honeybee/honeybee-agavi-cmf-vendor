<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

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
     * Retrieve the credential required to access this action.
     *
     * @return mixed
     */
    public function getCredentials()
    {
        return 'action.honeybee_core.theme_preview';
    }

    /**
     * @return boolean
     */
    public function isSecure()
    {
        return true;
    }

}
