<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomElement;
use AgaviXmlConfigDomDocument;

class MigrationConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/migration/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'migration');
        $config = $document->documentURI;

        $targets = [];
        foreach ($document->getConfigurationElements() as $config_node) {
            foreach ($config_node->get('migration_targets') as $migration_target_node) {
                $migration_target = $this->parseMigrationTarget($migration_target_node);
                $targets[$migration_target['name']] = $migration_target;
            }
        }

        $migration_config = [ 'targets' => $targets ];
        $config_code = sprintf('return %s;', var_export($migration_config, true));

        return $this->generate($config_code, $config);
    }

    protected function parseMigrationTarget(AgaviXmlConfigDomElement $migration_target_node)
    {
        $migration_loader_node = $migration_target_node->getChild('migration_loader');
        $collecor_class = $migration_loader_node->getAttribute('class');
        $collector_settings = $this->parseSettings($migration_loader_node);

        return [
            'name' => $migration_target_node->getAttribute('name'),
            'is_activated' => $this->parseValue($migration_target_node->getAttribute('active', true)),
            'migration_loader' => [ 'class' => $collecor_class, 'settings' => $collector_settings ],
            'settings' => $this->parseSettings($migration_target_node)
        ];
    }
}
