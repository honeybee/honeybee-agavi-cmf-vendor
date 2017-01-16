<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Security\Acl\Permission\Permission;
use Honeybee\Infrastructure\Security\Acl\Permission\PermissionList;
use Honeybee\Infrastructure\Security\Acl\Permission\PermissionListMap;
use Honeybee\Infrastructure\Security\Acl\Permission\PermissionServiceInterface;
use Honeybee\ServiceDefinitionInterface;

class PermissionServiceProvisioner extends AbstractProvisioner
{
    const ACL_CONFIG_NAME = 'access_control.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $state = [
            ':access_config' => $this->loadAclConfig(),
            ':additional_permissions' => $this->loadAdditionalPermissions(),
        ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(PermissionServiceInterface::CLASS, $service);
    }

    protected function loadAclConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::ACL_CONFIG_NAME
        );
    }

    protected function loadAdditionalPermissions()
    {
        $permissions_map = new PermissionListMap();

        $creds = include AgaviConfig::get('core.config_dir') . '/includes/action_credentials.php';

        foreach ($creds as $scope => $ops) {
            $permissions = new PermissionList;
            foreach ($ops as $op) {
                $permission_data = [
                    'type' => 'method',
                    'name' => $op,
                    'access_scope' => $scope,
                    'operation' => $op,
                ];
                $permissions->addItem(new Permission($permission_data));
            }
            $permissions_map->setItem($scope, $permissions);
        }

        return $permissions_map;
    }
}
