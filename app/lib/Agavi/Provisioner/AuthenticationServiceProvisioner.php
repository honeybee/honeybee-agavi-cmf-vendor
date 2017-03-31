<?php

namespace Honeygavi\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;

class AuthenticationServiceProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $state = [ ':config' => $service_definition->getConfig() ];

        $this->di_container->define($service, $state)->share($service);
    }
}
