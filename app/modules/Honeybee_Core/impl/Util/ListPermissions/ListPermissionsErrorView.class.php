<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_Core_Util_ListPermissions_ListPermissionsErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        if ($this->hasAttribute('error')) {
            return $this->cliError($this->getAttribute('error'));
        }

        $error_message = "Failure while getting the permissions.";

        if ($this->hasAttribute('errors')) {
            $error_message = $error_message . "\n" . implode("\n", $this->getAttribute('errors'));
        }

        return $this->cliError($error_message);
    }
}
