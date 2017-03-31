<?php

namespace Honeygavi\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;

class ServiceLocatorProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $this->di_container->share($service);
    }
}
