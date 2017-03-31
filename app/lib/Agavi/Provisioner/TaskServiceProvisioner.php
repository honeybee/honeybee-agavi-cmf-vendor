<?php

namespace Honeygavi\Agavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Model\Task\TaskServiceInterface;
use Honeybee\ServiceDefinitionInterface;

class TaskServiceProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $this->di_container->define($service, [])->share($service)->alias(TaskServiceInterface::CLASS, $service);
    }
}
