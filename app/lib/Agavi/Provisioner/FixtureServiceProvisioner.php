<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Fixture\FixtureTargetMap;
use Honeybee\Infrastructure\Fixture\FixtureTarget;
use Honeybee\Infrastructure\Fixture\FixtureServiceInterface;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use AgaviConfig;
use AgaviConfigCache;

class FixtureServiceProvisioner extends AbstractProvisioner
{
    const FIXTURE_CONFIG_NAME = 'fixture.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $that = $this;

        $factory_delegate = function (AggregateRootTypeMap $aggregate_root_type_map) use ($that, $service_definition) {
            $fixture_config = $that->loadFixtureConfig();
            $fixture_target_map = $that->buildFixtureTargetMap($fixture_config['targets']);

            $service_config = $service_definition->getConfig();
            $service_class = $service_definition->getClass();

            return new $service_class($service_config, $fixture_target_map, $aggregate_root_type_map);
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(FixtureServiceInterface::CLASS, $service);
    }

    protected function loadFixtureConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::FIXTURE_CONFIG_NAME
        );
    }

    protected function buildFixtureTargetMap(array $fixture_config)
    {
        $fixture_target_map = new FixtureTargetMap();

        foreach ($fixture_config as $target_name => $target_config) {
            $target_state = [
                ':name' => $target_name,
                ':is_activated' => $target_config['is_activated'],
                ':fixture_loader' => $this->buildFixtureLoader($target_config['fixture_loader'])
            ];

            $fixture_target = $this->di_container->make(FixtureTarget::CLASS, $target_state);
            $fixture_target_map->setItem($target_name, $fixture_target);
        }

        return $fixture_target_map;
    }

    protected function buildFixtureLoader(array $collector_config)
    {
        $collector_class = $collector_config['class'];

        if (!class_exists($collector_class)) {
            throw new RuntimeError(sprintf("Unable to load configured collector class: %s", $collector_class));
        }

        $collector_state = [ ':config' => new ArrayConfig($collector_config['settings']) ];

        return $this->di_container->make($collector_class, $collector_state);
    }
}
