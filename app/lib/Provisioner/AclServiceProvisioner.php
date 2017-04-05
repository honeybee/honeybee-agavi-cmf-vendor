<?php

namespace Honeygavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeygavi\Security\Acl\AclServiceInterface;
use Honeybee\ServiceDefinitionInterface;

class AclServiceProvisioner extends AbstractProvisioner
{
    const ACL_CONFIG_NAME = 'access_control.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $state = [
            ':roles_configuration' => $this->loadAclConfig(),
            ':additional_resources' => $this->loadAdditionalResources(),
        ];

        $this->di_container->define($service, $state)->share($service)->alias(AclServiceInterface::CLASS, $service);
    }

    protected function loadAclConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::ACL_CONFIG_NAME
        );
    }

    protected function loadAdditionalResources()
    {
        $creds = include AgaviConfig::get('core.config_dir') . '/includes/action_credentials.php';
        return array_keys($creds);
    }
}
