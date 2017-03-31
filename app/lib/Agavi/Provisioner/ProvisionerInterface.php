<?php

namespace Honeygavi\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;

interface ProvisionerInterface
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings);
}
