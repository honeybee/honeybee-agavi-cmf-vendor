<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviContext;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorServiceInterface;
use Honeybee\Infrastructure\Job\JobServiceInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\ServiceLocatorInterface;
use Psr\Log\LoggerInterface;

class JobServiceProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $factory_delegate = function (
            ConnectorServiceInterface $connector_service,
            ServiceLocatorInterface $service_locator,
            LoggerInterface $logger
        ) use (
            $service_definition,
            $provisioner_settings
        ) {
            $connector = $connector_service->getConnector($provisioner_settings->get('connection'));
            $config = $service_definition->getConfig();
            $service_class = $service_definition->getClass();

            return new $service_class($connector, $service_locator, $config, $logger);
        };

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(JobServiceInterface::CLASS, $service);
    }
}
