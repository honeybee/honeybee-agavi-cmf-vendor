<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Util_ListPermissions_ListPermissionsSuccessView extends View
{
    public function executeConsole(\AgaviRequestDataHolder $request_data)
    {
        $permissions = $this->getAttribute('permissions');

        if (empty($permissions)) {
            return "No permissions found!\n";
        }

        if ($request_data->hasParameter('role')) {
            echo  PHP_EOL . '# Role "' . $request_data->getParameter('role') . '" has the following permissions:' . PHP_EOL;
        } else {
            echo PHP_EOL . "All permissions:" . PHP_EOL;
        }

        foreach ($this->getAttribute('permissions') as $scope => $permission_list) {
            echo PHP_EOL . "- Scope: " . $scope . PHP_EOL . PHP_EOL . $permission_list;
        }

        if ($request_data->hasParameter('role')) {
            foreach ($this->getAttribute('parent_permissions') as $parent_role => $permission_map) {
                echo PHP_EOL . PHP_EOL . '----------' . PHP_EOL
                    . '# Following permission where inherited from "' . $parent_role . '": ' . PHP_EOL;
                foreach ($permission_map as $scope => $permission_list) {
                    echo PHP_EOL . "- Scope: " . $scope . PHP_EOL . PHP_EOL . $permission_list;
                }
            }
        }

        return PHP_EOL;
    }

}
