<?php

namespace Honeygavi\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use AgaviContext;
use Auryn\Injector as DiContainer;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorServiceInterface;
use Honeybee\Infrastructure\DataAccess\DataAccessServiceInterface;
use Honeybee\Infrastructure\DataAccess\Finder\FinderMap;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Infrastructure\DataAccess\Query\QueryTranslationInterface;
use Honeybee\Infrastructure\DataAccess\Storage\StorageReaderMap;
use Honeybee\Infrastructure\DataAccess\Storage\StorageWriterMap;
use Honeybee\Infrastructure\DataAccess\UnitOfWork\UnitOfWorkMap;
use Honeybee\ServiceDefinitionInterface;

class DataAccessServiceProvisioner extends AbstractProvisioner
{
    const DBAL_CONFIG_NAME = 'data_access.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $data_access_config = $this->loadDbalConfig();

        $this->provisionStorageWriterMap($data_access_config['storage_writers']);
        $this->provisionStorageReaderMap($data_access_config['storage_readers']);
        $this->provisionFinderMap($data_access_config['finders']);
        $this->provisionQueryServiceMap($data_access_config['query_services']);
        $this->provisionUnitOfWorkMap($data_access_config['units_of_work']);

        $service = $service_definition->getClass();
        $this->di_container->share($service)->alias(DataAccessServiceInterface::CLASS, $service);
    }

    protected function provisionStorageWriterMap(array $storage_writers)
    {
        $this->di_container->share(StorageWriterMap::CLASS)->delegate(
            StorageWriterMap::CLASS,
            function (DiContainer $di_container, ConnectorServiceInterface $connector_service) use ($storage_writers) {
                $map = [];
                foreach ($storage_writers as $map_key => $config) {
                    $object_state = [
                        ':config' => new ArrayConfig($config['settings']),
                        ':connector' => $connector_service->getConnector($config['connection'])
                    ];
                    foreach ($config['dependencies'] as $key => $dependency) {
                        $object_state[$key] = $dependency;
                    }
                    $map[$map_key] = $di_container->make($config['class'], $object_state);
                }

                return new StorageWriterMap($map);
            }
        );
    }

    protected function provisionStorageReaderMap(array $storage_readers)
    {
        $this->di_container->share(StorageReaderMap::CLASS)->delegate(
            StorageReaderMap::CLASS,
            function (DiContainer $di_container, ConnectorServiceInterface $connector_service) use ($storage_readers) {
                $map = [];
                foreach ($storage_readers as $map_key => $config) {
                    $object_state = [
                        ':config' => new ArrayConfig($config['settings']),
                        ':connector' => $connector_service->getConnector($config['connection'])
                    ];
                    foreach ($config['dependencies'] as $key => $dependency) {
                        $object_state[$key] = $dependency;
                    }
                    $map[$map_key] = $di_container->make($config['class'], $object_state);
                }

                return new StorageReaderMap($map);
            }
        );
    }

    protected function provisionFinderMap(array $finders)
    {
        $this->di_container->share(FinderMap::CLASS)->delegate(
            FinderMap::CLASS,
            function (DiContainer $di_container, ConnectorServiceInterface $connector_service) use ($finders) {
                $map = [];
                foreach ($finders as $map_key => $config) {
                    $object_state = [
                        ':config' => new ArrayConfig($config['settings']),
                        ':connector' => $connector_service->getConnector($config['connection'])
                    ];
                    foreach ($config['dependencies'] as $key => $dependency) {
                        $object_state[$key] = $dependency;
                    }
                    $map[$map_key] = $di_container->make($config['class'], $object_state);
                }

                return new FinderMap($map);
            }
        );
    }

    protected function provisionUnitOfWorkMap(array $units_of_work)
    {
        $this->di_container->share(UnitOfWorkMap::CLASS)->delegate(
            UnitOfWorkMap::CLASS,
            function (
                DiContainer $di_container,
                StorageWriterMap $storage_writer_map,
                StorageReaderMap $storage_reader_map
            ) use ($units_of_work) {
                $map = [];
                foreach ($units_of_work as $map_key => $config) {
                    $object_state = [
                        ':config' => new ArrayConfig($config['settings']),
                        ':event_reader' => $storage_reader_map->getItem($config['event_reader']),
                        ':event_writer' => $storage_writer_map->getItem($config['event_writer'])
                    ];
                    foreach ($config['dependencies'] as $key => $dependency) {
                        $object_state[$key] = $dependency;
                    }
                    $map[$map_key] = $di_container->make($config['class'], $object_state);
                }

                return new UnitOfWorkMap($map);
            }
        );
    }

    protected function provisionQueryServiceMap(array $query_services)
    {
        $this->di_container->share(QueryServiceMap::CLASS)->delegate(
            QueryServiceMap::CLASS,
            function (DiContainer $di_container, FinderMap $finder_map) use ($query_services) {
                $map = [];
                foreach ($query_services as $service_key => $service_config) {
                    $finder_mappings = [];
                    foreach ($service_config['finder_mappings'] as $finder_mapping_name => $finder_mapping) {
                        $finder_mappings[$finder_mapping_name] = [
                            'finder' => $finder_map->getItem($finder_mapping['finder']),
                            'query_translation' => $this->createQueryTranslation($finder_mapping['query_translation'])
                        ];
                    }

                    $object_state = array_merge(
                        $service_config['dependencies'],
                        [
                            ':config' => new ArrayConfig($service_config['settings']),
                            ':finder_mappings' => $finder_mappings
                        ]
                    );

                    $map[$service_key] = $di_container->make($service_config['class'], $object_state);
                }

                return new QueryServiceMap($map);
            }
        );
    }

    protected function createQueryTranslation(array $query_translation_settings)
    {
        $query_translation_impl = $query_translation_settings['class'];
        if (!$query_translation_impl) {
            throw new RuntimeError('Missing setting "query_translation" within ' . static::CLASS);
        }
        if (!class_exists($query_translation_impl)) {
            throw new RuntimeError(
                sprintf('Configured query-translation: "%s" does not exist!', $query_translation_impl)
            );
        }
        $query_translation = new $query_translation_impl(
            new ArrayConfig($query_translation_settings['settings'])
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

    protected function loadDbalConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::DBAL_CONFIG_NAME,
            AgaviContext::getInstance()->getName()
        );
    }
}
