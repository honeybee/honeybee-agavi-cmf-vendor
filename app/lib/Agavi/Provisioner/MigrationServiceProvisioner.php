<?php

namespace Honeygavi\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Migration\MigrationTargetMap;
use Honeybee\Infrastructure\Migration\MigrationTarget;
use Honeybee\Infrastructure\Migration\MigrationServiceInterface;
use Honeybee\ServiceDefinitionInterface;

class MigrationServiceProvisioner extends AbstractProvisioner
{
    const MIGRATION_CONFIG_NAME = 'migration.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $factory_delegate = function () use ($service_definition) {
            $migration_config = $this->loadMigrationConfig();
            $migration_target_map = $this->buildMigrationTargetMap($migration_config['targets']);

            $service_config = $service_definition->getConfig();
            $service_class = $service_definition->getClass();

            return new $service_class($service_config, $migration_target_map);
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(MigrationServiceInterface::CLASS, $service);
    }

    protected function loadMigrationConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::MIGRATION_CONFIG_NAME
        );
    }

    protected function buildMigrationTargetMap(array $migration_config)
    {
        $migration_target_map = new MigrationTargetMap();

        foreach ($migration_config as $target_name => $target_config) {
            $target_state = [
                ':name' => $target_name,
                ':is_activated' => $target_config['is_activated'],
                ':migration_loader' => $this->buildMigrationLoader($target_config['migration_loader']),
                ':config' => new ArrayConfig($target_config['settings'])
            ];

            $migration_target = $this->di_container->make(MigrationTarget::CLASS, $target_state);
            $migration_target_map->setItem($target_name, $migration_target);
        }

        return $migration_target_map;
    }

    protected function buildMigrationLoader(array $collector_config)
    {
        $collector_class = $collector_config['class'];

        if (!class_exists($collector_class)) {
            throw new RuntimeError(sprintf("Unable to load configured collector class: %s", $collector_class));
        }

        $collector_state = [ ':config' => new ArrayConfig($collector_config['settings']) ];

        return $this->di_container->make($collector_class, $collector_state);
    }
}
