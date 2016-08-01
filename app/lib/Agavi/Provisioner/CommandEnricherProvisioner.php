<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use Auryn\Injector as DiContainer;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Command\CommandEnricherInterface;
use Honeybee\ServiceDefinitionInterface;
use Psr\Log\LoggerInterface;

class CommandEnricherProvisioner extends AbstractProvisioner
{
    public function __construct(
        DiContainer $di_container,
        LoggerInterface $logger
    ) {
        parent::__construct($di_container, $logger);
    }

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $factory_delegate = function (DiContainer $di_container) use ($service_definition, $provisioner_settings) {
            $service = $service_definition->getClass();
            $command_enricher = new $service;
            foreach ($provisioner_settings->get('enrichers') as $enricher_class) {
                $command_enricher->addItem($di_container->make($enricher_class));
            }
            return $command_enricher;
        };

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(CommandEnricherInterface::CLASS, $service);
    }
}
