<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;

class WorkflowActivityServiceProvisioner extends ActivityServiceProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $activity_container_map = $this->buildActivityContainerMap();

        $service = $service_definition->getClass();

        $state = [ ':activity_container_map' => $activity_container_map ];

        $this->di_container->define($service, $state)->share($service);
    }
}
