<?php

namespace Honeygavi\ConfigHandler;

use AgaviXmlConfigDomDocument;

/**
 * ExportsConfigHandler parses configuration files that follow the honeybee exports markup.
 */
class ExportsConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/exports/1.0';

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
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'exports');
        $config = $document->documentURI;
        $exports = array();

        foreach ($document->getConfigurationElements() as $config_node) {
            $exports_node = $config_node->getChild('exports');

            foreach ($exports_node->get('export') as $export_node) {
                $export_name = $export_node->getAttribute('name');
                $export_class = $export_node->getAttribute('class');
                $export_description = $export_node->getChild('description')->nodeValue;

                $settings_node = $export_node->getChild('settings');
                $filters_node = $export_node->getChild('filters');
                $exports[$export_name] = array(
                    'class' => $export_class,
                    'settings' => $settings_node ? $this->parseSettings($settings_node) : array(),
                    'description' => $export_description,
                    'filters' => $filters_node ? $this->parseFilters($filters_node) : array()
                );
            }
        }

        $config_code = sprintf('return %s;', var_export($exports, true));

        return $this->generate($config_code, $config);
    }

    protected function parseFilters($filters_node)
    {
        $filters = array();
        foreach ($filters_node->get('filter') as $filter_node) {
            $filter_name = $filter_node->getAttribute('name');
            $filter_class = $filter_node->getAttribute('class');

            $settings_node = $filter_node->getChild('settings');
            $filters[$filter_name] = array(
                'class' => $filter_class,
                'settings' => $settings_node ? $this->parseSettings($settings_node) : array()
            );
        }

        return $filters;
    }
}
