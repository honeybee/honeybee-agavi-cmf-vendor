<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeygavi\Security\Acl\AclService;

class Honeybee_Core_Util_ListPermissionsAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $permissions = null;

        try {
            $acl_service = $this->getServiceLocator()->getAclService();
            $permission_service = $this->getServiceLocator()->getPermissionService();

            $role = $request_data->getParameter('role');

            $permissions = [];
            if (empty($role) || $role === AclService::ROLE_FULL_PRIV) {
                $permissions = $permission_service->getGlobalPermissions();
            } elseif (!empty($role) && $role !== AclService::ROLE_NON_PRIV) {
                $permissions = $permission_service->getRolePermissions($role);
            }

            $parent_permissions = [];
            $internal_roles = [ AclService::ROLE_FULL_PRIV, AclService::ROLE_NON_PRIV ];
            foreach ($acl_service->getRoleParents($role) as $parent_role) {
                if (in_array($parent_role, $internal_roles)) {
                    if ($parent_role === AclService::ROLE_FULL_PRIV) {
                        $parent_permissions[$parent_role] = $permission_service->getGlobalPermissions();
                    } else {
                        $parent_permissions[$parent_role] = [];
                    }
                } else {
                    $parent_permissions[$parent_role] = $permission_service->getRolePermissions($parent_role);
                }
            }

            $this->setAttribute('parent_permissions', $parent_permissions);
            $this->setAttribute('permissions', $permissions);
        } catch (Exception $e) {
            $this->setAttribute('error', $e->getMessage());
            return 'Error';
        }

        return 'Success';
    }
}
