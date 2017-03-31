<?php

namespace Honeygavi\Agavi\Provisioner;

use AgaviContext;
use Auryn\Injector as DiContainer;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Workflow\WorkflowServiceInterface;
use Honeybee\Infrastructure\Workflow\StateMachineBuilder;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\ServiceLocatorInterface;

class WorkflowServiceProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $factory_delegate = function (DiContainer $di_container) use ($service_definition, $provisioner_settings) {
            $config = $service_definition->getConfig();

            $logger_name = $provisioner_settings->get('logger', 'default');
            $logger = AgaviContext::getInstance()->getLoggerManager()->getLogger($logger_name)->getPsr3Logger();

            $builder_impl = $provisioner_settings->get('state_machine_builder', StateMachineBuilder::CLASS);
            $state_machine_builder = $di_container->make($builder_impl);

            $service_class = $service_definition->getClass();
            return new $service_class($config, $state_machine_builder, $logger);
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(WorkflowServiceInterface::CLASS, $service);
    }
}
