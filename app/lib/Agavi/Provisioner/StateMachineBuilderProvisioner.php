<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Workflow\StateMachineBuilderInterface;
use Honeybee\Infrastructure\Workflow\StateMachineConfigMap;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\ServiceLocatorInterface;

class StateMachineBuilderProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $factory_delegate = function (ServiceLocatorInterface $service_locator) use ($service_definition) {
            $state_machine_config_map = $this->buildStateMachineConfigMap();
            $service_class = $service_definition->getClass();
            return new $service_class($state_machine_config_map, $service_locator);
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(StateMachineBuilderInterface::CLASS, $service);
    }

    protected function buildStateMachineConfigMap()
    {
        return new StateMachineConfigMap();
    }
}
