<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\Ui\Renderer\RendererServiceInterface;

class RendererServiceProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $this->di_container->share($service)->alias(RendererServiceInterface::CLASS, $service);
    }
}
