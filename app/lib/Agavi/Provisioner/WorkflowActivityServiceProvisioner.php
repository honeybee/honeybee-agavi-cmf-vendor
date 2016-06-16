<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\ServiceLocatorInterface;

class WorkflowActivityServiceProvisioner extends ActivityServiceProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $factory_delegate = function (ServiceLocatorInterface $service_locator) use ($service_definition) {
            $activity_container_map = $this->buildActivityContainerMap();
            $service_class = $service_definition->getClass();
            return new $service_class($service_locator, $activity_container_map);
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service);
    }
}
