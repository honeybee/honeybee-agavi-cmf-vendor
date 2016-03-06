<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Auryn\Injector as DiContainer;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorMap;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorServiceInterface;
use Honeybee\ServiceDefinitionInterface;

class ConnectorServiceProvisioner extends AbstractProvisioner
{
    const CONNECTIONS_CONFIG_NAME = 'connections.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $that = $this;

        $this->di_container
            ->share(ConnectorMap::CLASS)
            ->prepare(
                ConnectorMap::CLASS,
                function (ConnectorMap $connector_map, DiContainer $di_container) use ($that) {
                    $connections_config = $that->loadConnectionsConfig();
                    $that->registerConnectors($connector_map, $connections_config);
                }
            );

        $service = $service_definition->getClass();

        $state = [ 'connector_map' => ConnectorMap::CLASS];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(ConnectorServiceInterface::CLASS, $service);
    }

    protected function loadConnectionsConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::CONNECTIONS_CONFIG_NAME
        );
    }

    protected function registerConnectors(ConnectorMap $connector_map, array $connections_config)
    {
        foreach ($connections_config as $connection_name => $connection_config) {
            $connector_class = $connection_config['class'];

            if (!class_exists($connector_class)) {
                throw new RuntimeError(sprintf('Unable to load configured connector class: %s', $connector_class));
            }

            $object_state = [
                ':name' => $connection_config['name'],
                ':config' => new ArrayConfig($connection_config['settings'])
            ];

            if (array_key_exists('dependencies', $connection_config) && is_array($connection_config['dependencies'])) {
                foreach ($connection_config['dependencies'] as $key => $dependency) {
                    $object_state[$key] = $dependency;
                }
            }

            $connector = $this->di_container->make($connector_class, $object_state);

            $connector_map->setItem($connection_name, $connector);
        }
    }
}
