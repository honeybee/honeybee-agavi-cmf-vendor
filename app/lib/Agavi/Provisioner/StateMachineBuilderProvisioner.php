<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Workflow\StateMachineBuilderInterface;
use Honeybee\Infrastructure\Workflow\StateMachineConfigMap;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\ServiceLocatorInterface;
use Workflux\Parser\Xml\StateMachineDefinitionParser;

class StateMachineBuilderProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $factory_delegate = function (ServiceLocatorInterface $service_locator) use ($service_definition) {
            $state_machine_definitions = $this->buildStateMachineDefinitions();
            $service_class = $service_definition->getClass();
            return new $service_class($state_machine_definitions, $service_locator);
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(StateMachineBuilderInterface::CLASS, $service);
    }

    protected function buildStateMachineDefinitions()
    {
        $state_machine_definitions = [];

        $parser = new StateMachineDefinitionParser();

        $xml_files = include AgaviConfig::get('core.config_dir') . '/includes/workflow_configs.php';
        foreach ($xml_files as $file) {
            $state_machine_definitions = array_merge($state_machine_definitions, $parser->parse($file));
        }

        return $state_machine_definitions;
    }
}
