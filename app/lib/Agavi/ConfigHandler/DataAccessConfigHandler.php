<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Common\Error\RuntimeError;

class DataAccessConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/data_access/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'dbal');

        $storage_writers = [];
        $storage_readers = [];
        $finders = [];
        $query_services = [];
        $units_of_work = [];

        foreach ($document->getConfigurationElements() as $configuration_element) {
            foreach ($configuration_element->get('storage_writers') as $storage_writer_element) {
                $storage_writer = $this->parseDbalComponentNode($storage_writer_element);
                $storage_writers[$storage_writer['name']] = $storage_writer;
            }
            foreach ($configuration_element->get('storage_readers') as $storage_reader_element) {
                $storage_reader = $this->parseDbalComponentNode($storage_reader_element);
                $storage_readers[$storage_reader['name']] = $storage_reader;
            }
            foreach ($configuration_element->get('finders') as $finder_element) {
                $finder = $this->parseDbalComponentNode($finder_element);
                $finders[$finder['name']] = $finder;
            }
            foreach ($configuration_element->get('query_services') as $query_service_element) {
                $query_service = $this->parseQueryServiceNode($query_service_element);
                $query_services[$query_service['name']] = $query_service;
            }
            $units_of_work_node = $configuration_element->getChild('units_of_work');
            if ($units_of_work_node) {
                foreach ($units_of_work_node->get('unit_of_work') as $uow_element) {
                    $uow = $this->parseUnitOfWorkNode($uow_element);
                    $units_of_work[$uow['name']] = $uow;
                }
            }
        }

        $dbal_config = [
            'storage_writers' => $storage_writers,
            'storage_readers' => $storage_readers,
            'finders' => $finders,
            'query_services' => $query_services,
            'units_of_work' => $units_of_work
        ];
        $configuration_code = sprintf('return %s;', var_export($dbal_config, true));

        return $this->generate($configuration_code, $document->documentURI);
    }

    protected function parseDbalComponentNode(AgaviXmlConfigDomElement $dbal_component_node)
    {
        $dbal_config = [
            'name' => $dbal_component_node->getAttribute('name'),
            'class' => $dbal_component_node->getAttribute('class'),
            'settings' => $this->parseSettings($dbal_component_node),
            'dependencies' => $this->parseDependencies($dbal_component_node)
        ];

        $connection_node = $dbal_component_node->getChild('connection');
        if ($connection_node) {
            $dbal_config['connection'] = $connection_node->getValue();
        }

        return $dbal_config;
    }

    protected function parseUnitOfWorkNode(AgaviXmlConfigDomElement $uow_node)
    {
        return [
            'name' => $uow_node->getAttribute('name'),
            'class' => $uow_node->getAttribute('class'),
            'settings' => $this->parseSettings($uow_node),
            'dependencies' => $this->parseDependencies($uow_node),
            'event_reader' => $uow_node->getChild('event_reader')->getValue(),
            'event_writer' => $uow_node->getChild('event_writer')->getValue()
        ];
    }

    protected function parseQueryServiceNode(AgaviXmlConfigDomElement $query_service_node)
    {
        return [
            'name' => $query_service_node->getAttribute('name'),
            'class' => $query_service_node->getAttribute('class'),
            'settings' => $this->parseSettings($query_service_node),
            'dependencies' => $this->parseDependencies($query_service_node),
            'finder_mappings' => $this->parseQueryServiceFinderMappings($query_service_node)
        ];
    }

    protected function parseQueryServiceFinderMappings(AgaviXmlConfigDomElement $query_service_node)
    {
        $finder_mappings = [];
        foreach ($query_service_node->get('finder_mappings') as $finder_mapping_element) {
            $finder_mapping_name = $finder_mapping_element->getAttribute('name');
            $query_translation_element = $finder_mapping_element->getChild('query_translation');
            $finder_mappings[$finder_mapping_name] = [
                'finder' => $finder_mapping_element->getChild('finder')->getValue(),
                'query_translation' => [
                    'class' => $query_translation_element->getAttribute('class'),
                    'settings' => $this->parseSettings($query_translation_element)
                ]
            ];
        }

        if (empty($finder_mappings)) {
            throw new RuntimeError('Missing at least one finder_mapping after parsing finder_mappings.');
        }

        return $finder_mappings;
    }

    protected function parseDependencies(AgaviXmlConfigDomElement $dbal_component_node)
    {
        $dependencies = [];
        $dependencies_node = $dbal_component_node->getChild('dependencies');

        if (!$dependencies_node) {
            return $dependencies;
        }

        foreach ($dependencies_node->get('dependencies') as $dependency_node) {
            $dependencies[$dependency_node->getAttribute('key')] = $dependency_node->getValue();
        }

        return $dependencies;
    }
}
