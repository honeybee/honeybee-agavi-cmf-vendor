<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

class ProcessConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/process/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'bus');

        $processes = [];

        foreach ($document->getConfigurationElements() as $configuration_node) {
            foreach ($configuration_node->get('processes') as $process_element) {
                $process_data = $this->parseProcessElement($process_element);
                $process_name = $process_data['name'];
                $processes[$process_name] = $process_data;
            }
        }

        $config_code = sprintf('return %s;', var_export($processes, true));

        return $this->generate($config_code, $document->documentURI);
    }

    protected function parseProcessElement(AgaviXmlConfigDomElement $process_element)
    {
        $process = [
            'name' => $process_element->getAttribute('name'),
            'builder' => $this->parseProcessBuilderElement($process_element->getChild('builder')),
            'settings' => $this->parseSettings($process_element)
        ];

        if ($process_element->hasAttribute('class')) {
            $process['class'] = $process_element->getAttribute('class');
        }

        return $process;
    }

    protected function parseProcessBuilderElement(AgaviXmlConfigDomElement $builder_element)
    {
        return [
            'class' => $builder_element->getAttribute('class'),
            'settings' => $this->parseSettings($builder_element)
        ];
    }
}
