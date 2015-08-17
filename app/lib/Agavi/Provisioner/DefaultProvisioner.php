<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use Honeybee\Common\Error\ConfigError;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;

class DefaultProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $state = [
            ':config' => $service_definition->getConfig()
        ];

        $this->di_container->define($service, $state);

        // there will only be one instance of the service when the "share" setting is true
        if ($provisioner_settings->get('share', true) === true) {
            $this->di_container->share($service);
        }

        if ($provisioner_settings->has('alias')) {
            $alias = $provisioner_settings->get('alias');
            if (!is_string($alias) && !class_exists($alias)) {
                throw new ConfigError('Alias given must be an existing class or interface name (fully qualified).');
            }
            $this->di_container->alias($alias, $service);
        }
    }
}
