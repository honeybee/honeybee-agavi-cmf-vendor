<?php

namespace Honeygavi\Agavi\Provisioner;

use Honeybee\Common\Error\ConfigError;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryTranslationInterface;
use Honeybee\ServiceDefinitionInterface;

class QueryServiceProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();
        $state = [
            ':config' => $service_definition->getConfig(),
            ':query_translation' => $this->buildQueryTranslation($provisioner_settings)
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

    protected function buildQueryTranslation(SettingsInterface $provisioner_settings)
    {
        $query_translation_impl = $provisioner_settings->get('query_translation')->get('class');
        if (!$query_translation_impl) {
            throw new RuntimeError('Missing setting "query_translation" within ' . static::CLASS);
        }
        if (!class_exists($query_translation_impl)) {
            throw new RuntimeError(
                sprintf('Configured query-translation: "%s" does not exist!', $query_translation_impl)
            );
        }
        $query_translation = new $query_translation_impl(
            new ArrayConfig((array)$provisioner_settings->get('query_translation')->get('config', []))
        );
        if (!$query_translation instanceof QueryTranslationInterface) {
            throw new RuntimeError(
                sprintf(
                    'Configured query-translation %s does not implement %s',
                    get_class($query_translation),
                    QueryTranslationInterface::CLASS
                )
            );
        }

        return $query_translation;
    }
}
