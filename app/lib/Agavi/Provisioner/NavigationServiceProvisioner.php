<?php

namespace Honeygavi\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeygavi\Ui\Navigation\NavigationServiceInterface;

class NavigationServiceProvisioner extends AbstractProvisioner
{
    const NAVIGATION_CONFIG_NAME = 'navigation.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $navigations_config = $this->loadNavigationsConfig();

        $default_navigation_name = $navigations_config['default_navigation'];

        $service = $service_definition->getClass();

        $state = [
            ':navigations_config' => new ArrayConfig($navigations_config['navigations']),
            ':default_navigation' => $default_navigation_name
        ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(NavigationServiceInterface::CLASS, $service);
    }

    protected function loadNavigationsConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::NAVIGATION_CONFIG_NAME
        );
    }
}
