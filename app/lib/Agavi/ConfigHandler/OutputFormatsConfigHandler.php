<?php

namespace Honeygavi\Agavi\ConfigHandler;

use Honeybee\Common\Error\ConfigError;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

/**
 * Output formats are meta data used for output type switching in routing (matching Accept headers)
 * and locating the correct renderer for subjects.
 */
class OutputFormatsConfigHandler extends BaseConfigHandler
{
    /**
     * Name of the output formats schema namespace.
     */
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/output_formats/1.0';

    /**
     * Execute this configuration handler.
     *
     * @param AgaviXmlConfigDomDocument $document configuration document
     *
     * @return string data to be written to a cache file
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'output_formats');
        $output_formats = array();

        // iterate over configuration nodes and collect output formats
        foreach ($document->getConfigurationElements() as $configuration) {
            $output_formats = array_merge($output_formats, $this->parseOutputFormats($configuration, $document));
        }

        $config_code = sprintf('return %s;', var_export($output_formats, true));

        return $this->generate($config_code, $document->documentURI);
    }

    protected function parseOutputFormats(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $formats = array();

        foreach ($node->get('output_formats') as $format_node) {
            $name = $format_node->hasAttribute('name') ? trim($format_node->getAttribute('name')) : '';
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "%s" element.',
                        $document->documentURI,
                        $format_node->getName()
                    )
                );
            }

            $renderer_locator = '';
            if ($format_node->hasAttribute('renderer_locator')) {
                $renderer_locator = trim($format_node->getAttribute('renderer_locator'));
            }

            $media_type_info = $this->parseMediaTypeInfo($format_node, $document);

            $acceptable_content_types = [];
            if ($node->hasChild('acceptable_content_types')) {
                foreach ($node->getChild('acceptable_content_types')->get('accept') as $accept_node) {
                    $acceptable_content_types[] = trim($accept_node->getValue());
                }
            } else {
                // if no explicit empty acceptable_content_types node exists, default to the media type's template
                if (isset($media_type_info['template'])) {
                    $acceptable_content_types = [
                        $media_type_info['template']
                    ];
                }
            }

            $content_type = '';
            if ($node->hasChild('content_type')) {
                $content_type = trim($node->getChild('content_type')->getValue());
            } else {
                // if no explicit empty content_type node exists, default to the media type's template
                if (isset($media_type_info['template'])) {
                    $content_type = $media_type_info['template'];
                }
            }

            $formats[$name] = [
                'name' => $name,
                'renderer_locator' => $renderer_locator,
                'content_type' => $content_type,
                'acceptable_content_types' => $acceptable_content_types,
                'media_type_info' => $media_type_info
             ];
        }

        return $formats;
    }

    protected function parseMediaTypeInfo(AgaviXmlConfigDomElement $node, AgaviXmlConfigDomDocument $document)
    {
        $infos = [];
        if (!$node->hasChild('media_type_info')) {
            return $infos;
        }

        $info = $node->getChild('media_type_info');

        $string_fields = [
            'name', 'template', 'type', 'subtype', 'suffix', 'abstract',
            'description', 'security_considerations', 'encoding_considerations'
        ];

        foreach ($string_fields as $name) {
            if ($info->hasChild($name)) {
                $infos[$name] = $info->getChild($name)->getValue();
            }
        }

        $list_fields = [ 'template_alternatives', 'file_extensions' ];
        foreach ($list_fields as $list_field_name) {
            $infos[$list_field_name] = [];
            foreach ($info->get($list_field_name) as $list_field_node) {
                $infos[$list_field_name][] = $list_field_node->getValue();
            }
        }

        $map_fields = [ 'template_alternatives', 'file_extensions' ];
        foreach ($map_fields as $map_field_name) {
            $infos[$map_field_name] = [];
            foreach ($info->get($map_field_name) as $map_field_node) {
                $name = $map_field_node->hasAttribute('name') ? trim($map_field_node->getAttribute('name')) : '';
                $infos[$map_field_name][$name] = $map_field_node->getValue();
            }
        }

        return $infos;
    }
}
