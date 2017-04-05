<?php

namespace Honeygavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeygavi\Ui\ViewConfig\NameResolverInterface;
use Honeygavi\Ui\ViewConfig\SubjectNameResolver;
use Honeygavi\Ui\ViewConfig\ViewConfigServiceInterface;

class ViewConfigServiceProvisioner extends AbstractProvisioner
{
    const VIEW_CONFIG_NAME = 'view_configs.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $view_config = $this->loadViewConfig();

        $service = $service_definition->getClass();

        $state = [
            ':config' => $service_definition->getConfig(),
            ':view_config' => new ArrayConfig($view_config),
        ];

        $this->di_container->alias(NameResolverInterface::CLASS, SubjectNameResolver::CLASS);

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(ViewConfigServiceInterface::CLASS, $service);
    }

    protected function loadViewConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::VIEW_CONFIG_NAME
        );
    }
}
