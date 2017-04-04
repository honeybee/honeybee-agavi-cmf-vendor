<?php

namespace Honeygavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

class ImportsConfigHandler extends BaseConfigHandler
{
    /**
     * Holds the name of the data_import document schema namespace.
     */
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/imports/1.0';

    /**
     * Execute this configuration handler.
     *
     * @param      string An absolute filesystem path to a configuration file.
     * @param      string An optional context in which we are currently running.
     *
     * @return     string Data to be written to a cache file.
     *
     * @throws     <b>AgaviUnreadableException</b> If a requested configuration
     *                                             file does not exist or is not
     *                                             readable.
     * @throws     <b>AgaviParseException</b> If a requested configuration file is
     *                                        improperly formatted.
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'consumer');
        $config = $document->documentURI;

        $parsed_consumer_definitions = array();
        /* @var $config_node AgaviXmlConfigDomElement */
        foreach ($document->getConfigurationElements() as $config_node) {
            $parsed_consumer_definitions = array_merge_recursive(
                $parsed_consumer_definitions,
                $this->parseConsumerDefinitions(
                    $config_node->getChild('consumers')
                )
            );
        }

        $data = array('consumers' => $parsed_consumer_definitions);
        $config_code = sprintf('return %s;', var_export($data, true));

        return $this->generate($config_code, $config);
    }

    /**
     * Parse the given consumers node and return the corresponding array representation.
     *
     * @param AgaviXmlConfigDomElement $consumers_element
     *
     * @return array
     */
    protected function parseConsumerDefinitions(AgaviXmlConfigDomElement $consumers_element)
    {
        $parsed_consumer_definitions = array();

        foreach ($consumers_element->getChildren('consumer') as $consumer_element) {
            $name = trim($consumer_element->getAttribute('name'));
            $parsed_consumer_definitions[$name] = $this->parseConsumerDefinition($consumer_element);
        }

        return $parsed_consumer_definitions;
    }

    protected function parseConsumerDefinition(AgaviXmlConfigDomElement $consumer_element)
    {
        $settings = array();
        if (($settings_element = $consumer_element->getChild('settings'))) {
            $settings = $this->parseSettings($settings_element);
        }

        $filters = array();
        foreach ($consumer_element->getChild('filters')->get('filter') as $filter_element) {
            $filter_settings = array();
            if (($filter_settings_element = $filter_element->getChild('settings'))) {
                $filter_settings = $this->parseSettings($filter_settings_element);
            }

            $filters[] = array(
                'name' => trim($filter_element->getAttribute('name')),
                'class' => trim($filter_element->getAttribute('class')),
                'settings' => $filter_settings
            );
        }

        $description_node = $consumer_element->getChild('description');
        $provider_element = $consumer_element->getChild('provider');
        $parsed_provider = $this->parseProviderDefinition($provider_element);

        return array(
            'name' => trim($consumer_element->getAttribute('name')),
            'description' => $description_node ? trim($description_node->getValue()) : '',
            'class' => trim($consumer_element->getAttribute('class')),
            'settings' => $settings,
            'filters' => $filters,
            'provider' => $parsed_provider
        );
    }

    protected function parseProviderDefinition(AgaviXmlConfigDomElement $provider_element)
    {
        $description_node = $provider_element->getChild('description');

        $provider_definition = array();
        $provider_definition['name'] = $provider_element->getAttribute('name');
        $provider_definition['class'] = $provider_element->getAttribute('class');
        $provider_definition['description'] = $description_node ? trim($description_node->getValue()) : '';

        $settings = array();
        if (($settings_element = $provider_element->getChild('settings'))) {
            $settings = $this->parseSettings($settings_element);
        }
        $provider_definition['settings'] = $settings;

        return $provider_definition;
    }
}
