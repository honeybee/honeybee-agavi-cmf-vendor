<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviContext;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\Ui\UrlGeneratorInterface;

class UrlGeneratorProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $state = [
            ':config' => $service_definition->getConfig(),
            ':routing' => AgaviContext::getInstance()->getRouting(),
        ];

        $this->di_container->define($service, $state)
            ->share($service)
            ->alias(UrlGeneratorInterface::CLASS, $service);
    }
}
