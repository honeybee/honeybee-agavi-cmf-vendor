<?php

namespace Honeygavi\Agavi\ConfigHandler;

use Honeybee\Common\Error\ConfigError;
use Honeybee\Common\Util\ArrayToolkit;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

/**
 * ViewTemplates configuration files contain template elements that define
 * the layout and grouping of rendered fields. Usually referenced in the
 * renderer settings in the views.xml files and then used for rendering
 * the primary content of a page.
 */
class ViewTemplatesConfigHandler extends BaseConfigHandler
{
    /**
     * Name of the view templates schema namespace.
     */
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/view_templates/1.0';

    /**
     * Execute this configuration handler.
     *
     * @param AgaviXmlConfigDomDocument $document configuration document
     *
     * @return string data to be written to a cache file
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'view_templates');

        $all_view_templates = [];

        // iterate over configuration nodes and collect templates
        foreach ($document->getConfigurationElements() as $configuration) {
            $all_view_templates = array_merge(
                $all_view_templates,
                $this->parseViewTemplates($configuration, $document)
            );
        }

        // TODO recursively extend view_configs
        for ($i=0; $i<5; $i++) {
            // when view_templates have an "extends" attribute with a valid scope name, we merge scopes
            foreach ($all_view_templates as $view_scope => &$view_templates) {
                if (!empty($view_templates['extends'])) {
                    if (empty($all_view_templates[$view_templates['extends']])) {
                        throw new ConfigError(
                            sprintf(
                                'The "extends" attribute value of the scope "%s" view_templates node is invalid. ' .
                                'No view_templates node with scope "%s" found in configuration file "%s".',
                                $view_scope,
                                $view_templates['extends'],
                                $document->documentURI
                            )
                        );
                    }
                    $view_templates = array_replace_recursive(
                        $all_view_templates[$view_templates['extends']],
                        $view_templates
                    );
                }
                // unset($view_templates['extends']);
            }
        }
        // var_dump($all_view_templates);die;
        $config_code = sprintf('return %s;', var_export($all_view_templates, true));

        return $this->generate($config_code, $document->documentURI);
    }

    protected function parseViewTemplates(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $vts_node = $node->getChild('view_templates');

        if (!$vts_node) {
            return [];
        }

        $scope = $vts_node->hasAttribute('scope') ? trim($vts_node->getAttribute('scope')) : '';
        if (empty($scope)) {
            throw new ConfigError(
                sprintf(
                    'Configuration file "%s" must specify a "scope" attribute for a "view_templates" element.',
                    $document->documentURI
                )
            );
        }

        $extends_scope = $vts_node->hasAttribute('extends') ? trim($vts_node->getAttribute('extends')) : '';

        $view_templates = [];
        $view_templates[$scope] = [];
        $view_templates[$scope]['scope'] = $scope;
        $view_templates[$scope]['extends'] = $extends_scope;
        $view_templates[$scope]['view_templates'] = [];

        foreach ($vts_node->get('view_template') as $vt_node) {
            $name = $vt_node->hasAttribute('name') ? trim($vt_node->getAttribute('name')) : '';
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "%s" element.',
                        $document->documentURI,
                        $vt_node->getName()
                    )
                );
            }
            $css = $vt_node->hasAttribute('css') ? trim($vt_node->getAttribute('css')) : '';

            $view_templates[$scope]['view_templates'][$name] = [];
            $view_templates[$scope]['view_templates'][$name]['name'] = $name;
            $view_templates[$scope]['view_templates'][$name]['css'] = $css;
            $view_templates[$scope]['view_templates'][$name]['tabs'] = $this->parseTabs($vt_node, $document);
        }

        return $view_templates;
    }

    protected function parseTabs(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $tabs = [];

        foreach ($node->get('tabs') as $tab_node) {
            $name = $tab_node->hasAttribute('name') ? trim($tab_node->getAttribute('name')) : '';
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "%s" element.',
                        $document->documentURI,
                        $tab_node->getName()
                    )
                );
            }
            $css = $tab_node->hasAttribute('css') ? trim($tab_node->getAttribute('css')) : '';

            $tabs[$name] = [
                'name' => $name,
                'css' => $css,
                'panels' => $this->parsePanels($tab_node, $document)
            ];
        }

        return $tabs;
    }

    protected function parsePanels(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $panels = [];

        foreach ($node->get('panels') as $panel_node) {
            $name = $panel_node->hasAttribute('name') ? trim($panel_node->getAttribute('name')) : '';
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "%s" element.',
                        $document->documentURI,
                        $panel_node->getName()
                    )
                );
            }
            $css = $panel_node->hasAttribute('css') ? trim($panel_node->getAttribute('css')) : '';

            $panels[] = [
                'name' => $name,
                'css' => $css,
                'rows' => $this->parseRows($panel_node, $document)
            ];
        }

        return $panels;
    }

    protected function parseRows(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $rows = [];

        foreach ($node->get('rows') as $row_node) {
            $css = $row_node->hasAttribute('css') ? trim($row_node->getAttribute('css')) : '';
            $rows[] = [
                'css' => $css,
                'cells' => $this->parseCells($row_node, $document)
            ];
        }

        return $rows;
    }

    protected function parseCells(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $cells = [];

        foreach ($node->get('cells') as $cell_node) {
            $css = $cell_node->hasAttribute('css') ? trim($cell_node->getAttribute('css')) : '';
            $cells[] = array(
                'css' => $css,
                'groups' => $this->parseGroups($cell_node, $document)
            );
        }

        return $cells;
    }

    protected function parseGroups(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $groups = [];

        foreach ($node->get('groups') as $group_node) {
            $name = $group_node->hasAttribute('name') ? trim($group_node->getAttribute('name')) : '';
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "%s" element.',
                        $document->documentURI,
                        $group_node->getName()
                    )
                );
            }
            $css = $group_node->hasAttribute('css') ? trim($group_node->getAttribute('css')) : '';

            $groups[$name] = [
                'name' => $name,
                'css' => $css,
                'fields' => $this->parseFields($group_node, $document)
            ];
        }

        return $groups;
    }

    protected function parseFields(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $fields = [];

        foreach ($node->get('fields') as $field_node) {
            $name = $field_node->hasAttribute('name') ? trim($field_node->getAttribute('name')) : '';

            $attributes = $field_node->getAttributes();

            if ($field_node->hasAttribute('css')) {
                $attributes['css'] = trim($field_node->getAttribute('css'));
            }

            if ($field_node->hasAttribute('template')) {
                $attributes['template'] = trim($field_node->getAttribute('template'));
            }

            if ($field_node->hasAttribute('renderer')) {
                $attributes['renderer'] = trim($field_node->getAttribute('renderer'));
            }

            $settings = $this->parseSettings($field_node);

            // ANY attributes will be merged over the settings from the field to not always need to
            // have a settings/setting node when an attribute on the field element is sufficient
            $fields[$name] = array_merge($settings, $attributes);
        }

        return $fields;
    }
}
