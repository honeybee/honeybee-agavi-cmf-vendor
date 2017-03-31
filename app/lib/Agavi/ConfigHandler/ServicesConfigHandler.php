<?php

namespace Honeygavi\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

class ServicesConfigHandler extends BaseConfigHandler
{
    const NODE_SERVICE_MAP = 'service_map';

    const NODE_SERVICE_DEFINITIONS = 'service_definitions';

    const NODE_SERVICE_DEFINITION = 'service';

    const NODE_CLASS = 'class';

    const NODE_PROVISIONER = 'provisioner';

    const NODE_METHOD = 'method';

    const NAMESPACE_PREFIX = 'service';

    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/services/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, self::NAMESPACE_PREFIX);
        $service_map = array();
        foreach ($document->getConfigurationElements() as $configuration) {
            $service_map_node = $configuration->getChild(self::NODE_SERVICE_MAP);
            if ($service_map_node) {
                $next_service_map = $this->parseServiceDefinitionMap($service_map_node);
                $service_map = array_merge_recursive($service_map, $next_service_map);
            }
        }
        $config_code = $this->generateCacheCode($service_map);

        return $this->generate($config_code, $document->documentURI);
    }

    protected function parseServiceDefinitionMap(AgaviXmlConfigDomElement $service_map_node)
    {
        $settings_element = $service_map_node->getChild(self::NODE_SETTINGS);
        $settings = array();
        if ($settings_element) {
            $settings = $this->parseSettings($settings_element);
        }

        $service_definitions = array();
        $service_definitions_node = $service_map_node->getChild(self::NODE_SERVICE_DEFINITIONS);

        if ($service_definitions_node) {
            foreach ($service_definitions_node->getChildren(self::NODE_SERVICE_DEFINITION) as $service_node) {
                $service_data = $this->parseService($service_node);
                $service_definitions[$service_data['name']] = $service_data;
            }
        }

        return [ 'options' => $settings, 'service_definitions' => array_values($service_definitions) ];
    }

    protected function parseService(AgaviXmlConfigDomElement $service_node)
    {
        $service_config = array();

        $settings_node = $service_node->getChild(self::NODE_SETTINGS);
        if ($settings_node) {
            $service_config['options'] = $this->parseSettings($settings_node);
        } else {
            $service_config['options'] = array();
        }

        $class_node = $service_node->getChild(self::NODE_CLASS);
        $provisioner_node = $service_node->getChild(self::NODE_PROVISIONER);
        $service_config['class'] = $class_node->getValue();

        if ($provisioner_node) {
            $class_node = $provisioner_node->getChild(self::NODE_CLASS);

            $provisioner_method = '';
            if ($provisioner_node->hasChild(self::NODE_METHOD)) {
                $provisioner_method = $provisioner_node->getChild(self::NODE_METHOD)->getValue();
            }

            $provisioner_settings_node = $provisioner_node->getChild(self::NODE_SETTINGS);
            $provisioner_settings = array();
            if ($provisioner_settings_node) {
                $provisioner_settings = $this->parseSettings($provisioner_settings_node);
            }

            $service_config['provisioner'] = array(
                'class' => $class_node->getValue(),
                'method' => $provisioner_method,
                'settings' => $provisioner_settings
            );
        }

        $service_config['name'] = $service_node->getAttribute(self::ATTRIBUTE_NAME);

        return $service_config;
    }

    protected function generateCacheCode(array $service_map)
    {
        $code_lines = array(
            sprintf($this->getBaseCodeTemplate(), var_export($service_map['options'], true))
        );

        foreach ($service_map['service_definitions'] as $service_definition) {
            $code_lines[] = sprintf(
                $this->getServiceDefinitionTemplate(),
                var_export($service_definition['name'], true),
                var_export($service_definition, true)
            );
        }

        $code_lines[] = 'return $service_map;';

        return implode("\n", $code_lines);
    }

    protected function getBaseCodeTemplate()
    {
        return <<<TPL

\$service_map = new Honeybee\\ServiceDefinitionMap(
    new Trellis\\Common\\Options(
        %s
    )
);
TPL;
    }

    protected function getServiceDefinitionTemplate()
    {
        return <<<TPL
\$service_map->setItem(
    %s,
    new Honeybee\\ServiceDefinition(
        %s
    )
);
TPL;
    }
}
