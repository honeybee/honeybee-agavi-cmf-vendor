<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviContext;
use AgaviConfigCache;
use AgaviConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorServiceInterface;
use Honeybee\Infrastructure\Job\JobMap;
use Honeybee\Infrastructure\Job\JobServiceInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\ServiceLocatorInterface;
use Psr\Log\LoggerInterface;

class JobServiceProvisioner extends AbstractProvisioner
{
    const JOBS_CONFIG_FILE = 'jobs.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $jobs_config = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::JOBS_CONFIG_FILE,
            AgaviContext::getInstance()->getName()
        );

        $service = $service_definition->getClass();

        $factory_delegate = function (
            ConnectorServiceInterface $connector_service,
            ServiceLocatorInterface $service_locator,
            LoggerInterface $logger
        ) use (
            $service_definition,
            $provisioner_settings,
            $jobs_config
        ) {
            $connector = $connector_service->getConnector($provisioner_settings->get('connection'));
            $config = $service_definition->getConfig();
            $service_class = $service_definition->getClass();

            return new $service_class($connector, $service_locator, new JobMap($jobs_config), $config, $logger);
        };

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(JobServiceInterface::CLASS, $service);
    }
}
