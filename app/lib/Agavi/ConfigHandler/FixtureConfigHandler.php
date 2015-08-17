<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomElement;
use AgaviXmlConfigDomDocument;

class FixtureConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/fixture/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'fixture');
        $config = $document->documentURI;

        $targets = [];
        foreach ($document->getConfigurationElements() as $config_node) {
            foreach ($config_node->get('fixture_targets') as $fixture_target_node) {
                $fixture_target = $this->parseFixtureTarget($fixture_target_node);
                $targets[$fixture_target['name']] = $fixture_target;
            }
        }

        $fixture_config = [ 'targets' => $targets ];
        $config_code = sprintf('return %s;', var_export($fixture_config, true));

        return $this->generate($config_code, $config);
    }

    protected function parseFixtureTarget(AgaviXmlConfigDomElement $fixture_target_node)
    {
        $fixture_loader_node = $fixture_target_node->getChild('fixture_loader');
        $collecor_class = $fixture_loader_node->getAttribute('class');
        $collector_settings = $this->parseSettings($fixture_loader_node);

        return [
            'name' => $fixture_target_node->getAttribute('name'),
            'is_activated' => $this->parseValue($fixture_target_node->getAttribute('active', true)),
            'fixture_loader' => [ 'class' => $collecor_class, 'settings' => $collector_settings ]
        ];
    }
}
