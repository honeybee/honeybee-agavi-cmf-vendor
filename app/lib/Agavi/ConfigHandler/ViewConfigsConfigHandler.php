<?php

namespace Honeygavi\Agavi\ConfigHandler;

use Honeybee\Common\Error\ConfigError;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

/**
 * ViewConfigs configuration files contain view elements that define settings
 * used by actions/views. This includes simple settings like translation
 * domains etc., but may also be used to control which slots to render
 * or what renderers to use for what parts of the generated page/output.
 */
class ViewConfigsConfigHandler extends BaseConfigHandler
{
    /**
     * Name of the view_configs settings schema namespace.
     */
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/view_configs/1.0';

    /**
     * Execute this configuration handler.
     *
     * @param AgaviXmlConfigDomDocument $document configuration document
     *
     * @return string data to be written to a cache file
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'view_configs');

        $view_configs = [];

        // iterate over configuration nodes and merge settings recursively
        foreach ($document->getConfigurationElements() as $configuration) {
            $new_view_configs = $this->parseViewConfigs($configuration, $document);
            $view_configs = self::mergeSettings($view_configs, $new_view_configs);
        }

        // TODO recursively extend view_configs
        for ($i=0; $i<5; $i++) {
            // when a view_config has an "extends" attribute with a valid scope name, we merge scopes
            foreach ($view_configs as $view_scope => &$view_config) {
                if (!empty($view_config['extends'])) {
                    if (empty($view_configs[$view_config['extends']])) {
                        throw new ConfigError(
                            sprintf(
                                'The "extends" attribute value of the view_config with scope "%s" is invalid. ' .
                                'No view_config with scope "%s" found in configuration file "%s".',
                                $view_scope,
                                $view_config['extends'],
                                $document->documentURI
                            )
                        );
                    }
                    $view_config = self::mergeSettings($view_configs[$view_config['extends']], $view_config);
                }
                //unset($view_config['extends']);
            }
        }
        // var_dump($view_configs);die;
        $config_code = sprintf('return %s;', var_export($view_configs, true));

        return $this->generate($config_code, $document->documentURI);
    }

    /**
     * Returns the view_configs from the given configuration node.
     *
     * @param AgaviXmlConfigDomElement $configuration configuration node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array of view_configs with their respective settings
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseViewConfigs(AgaviXmlConfigDomElement $configuration, AgaviXmlConfigDomDocument $document)
    {
        $views_element = $configuration->getChild('view_configs');

        /*
        // we need a default view to use
        if (!$views_element->hasAttribute('default'))
        {
            throw new ConfigError(
                sprintf(
                    'Configuration file "%s" must specify a default view to use via ' .
                    'the "default" attribute on the "view_configs" element.',
                    $document->documentURI
                )
            );
        }
        $default_view_scope = $views_element->getAttribute('default');
        */

        $views = [];

        // there may be multiple views, each should have a scope
        foreach ($views_element->getChildren('view_config') as $view) {
            $view_scope = $view->hasAttribute('scope') ? trim($view->getAttribute('scope')) : '';
            if (empty($view_scope)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "scope" attribute for a "view_config" element.',
                        $document->documentURI
                    )
                );
            }

            $extends_scope = $view->hasAttribute('extends') ? trim($view->getAttribute('extends')) : '';

            // parse all settings from given settings node
            $settings_node = $view->getChild('settings');
            $settings = $settings_node ? $this->parseSettings($settings_node) : [];

            $views[$view_scope] = [];
            $views[$view_scope]['scope'] = $view_scope;
            $views[$view_scope]['extends'] = $extends_scope;
            $views[$view_scope]['settings'] = $settings;
            $views[$view_scope]['activities'] = $this->parseActivities($view, $document);
            $views[$view_scope]['slots'] = $this->parseSlots($view, $document);
            $views[$view_scope]['output_formats'] = $this->parseOutputFormats($view, $document);
        }

        return $views;
    }

    /**
     * Returns the activities from the given node.
     *
     * @param AgaviXmlConfigDomElement $current_node current node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array of activities
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseActivities(AgaviXmlConfigDomElement $current_node, AgaviXmlConfigDomDocument $document)
    {
        $activities = [];

        foreach ($current_node->get('activities') as $node) {
            $scope = $node->hasAttribute('scope') ? trim($node->getAttribute('scope')) : '';
            if (empty($scope)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "scope" attribute for a "%s" element.',
                        $document->documentURI,
                        $node->getName()
                    )
                );
            }

            $name = $node->getValue() ?: '';
            $name = trim($name);
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify an activity name as node value for "%s" elements.',
                        $document->documentURI,
                        $node->getName()
                    )
                );
            }

            $activity_name = $scope . '.' . $name;

            $activities[$activity_name] = [
                'activity' => $activity_name,
                'name' => $name,
                'scope' => $scope
            ];
        }

        return $activities;
    }

    /**
     * Returns the output formats from the given node.
     *
     * @param AgaviXmlConfigDomElement $current_node current node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array of output formats
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseOutputFormats(AgaviXmlConfigDomElement $current_node, AgaviXmlConfigDomDocument $document)
    {
        $output_formats = [];

        foreach ($current_node->get('output_formats') as $node) {
            $identifier = $node->hasAttribute('name') ? trim($node->getAttribute('name')) : '';
            if (empty($identifier)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "%s" element.',
                        $document->documentURI,
                        $node->getName()
                    )
                );
            }

            $output_formats[$identifier] = $this->parseRendererConfigs($node, $document);
        }

        return $output_formats;
    }

    /**
     * Returns the slots from the given node.
     *
     * @param AgaviXmlConfigDomElement $current_node current node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array of slots with their respective settings
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseSlots(AgaviXmlConfigDomElement $current_node, AgaviXmlConfigDomDocument $document)
    {
        $slots = [];

        foreach ($current_node->get('slots') as $slot) {
            $name = $slot->hasAttribute('name') ? trim($slot->getAttribute('name')) : '';
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "slot" element.',
                        $document->documentURI
                    )
                );
            }

            $settings_node = $slot->getChild('settings');
            $settings = $settings_node ? $this->parseSettings($settings_node) : [];

            $slots[$name] = $settings;
        }

        return $slots;
    }

    /**
     * Returns the renderer_configs from the given node.
     *
     * @param AgaviXmlConfigDomElement $current_node current node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array of renderer_configs with their respective settings
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseRendererConfigs(AgaviXmlConfigDomElement $current_node, AgaviXmlConfigDomDocument $document)
    {
        $renderer_configs = [];

        foreach ($current_node->get('renderer_configs') as $renderer_config) {
            $subject_name = '';
            if ($renderer_config->hasAttribute('subject')) {
                $subject_name = trim($renderer_config->getAttribute('subject'));
            }
            if (empty($subject_name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "subject" attribute for a "renderer_config" element.',
                        $document->documentURI
                    )
                );
            }

            $settings_node = $renderer_config->getChild('settings');
            $settings = $settings_node ? $this->parseSettings($settings_node) : [];

            $implementor = '';
            if ($renderer_config->hasAttribute('implementor')) {
                $implementor = trim($renderer_config->getAttribute('implementor'));
            }
            if (!empty($implementor)) {
                // map attribute name to settings property as "renderer" is being used in
                // RendererLocator and merged with the viewtemplate field settings "renderer"
                $settings['renderer'] = $implementor;
            }

            $renderer_configs[$subject_name] = $settings;
        }

        return $renderer_configs;
    }
}
