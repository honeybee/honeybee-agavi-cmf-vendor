<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

class ConnectorConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/connections/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'connections');

        $connections = array();

        foreach ($document->getConfigurationElements() as $configuration_element) {
            $parsed_connections = $this->parseConnections($configuration_element);
            $connections = self::mergeSettings($connections, $parsed_connections);
        }

        $configuration_code = sprintf('return %s;', var_export($connections, true));

        return $this->generate($configuration_code, $document->documentURI);
    }

    protected function parseConnections(AgaviXmlConfigDomElement $configuration)
    {
        $connections = array();
        foreach ($configuration->get('connections') as $connection_element) {
            $connection = $this->parseConnection($connection_element);
            $connections[$connection['name']] = $connection;
        }

        return $connections;
    }

    protected function parseConnection(AgaviXmlConfigDomElement $connection_element)
    {
        return array(
            'name' =>$connection_element->getAttribute('name'),
            'class' => $connection_element->getAttribute('class'),
            'settings' => $this->parseSettings($connection_element)
        );
    }
}
