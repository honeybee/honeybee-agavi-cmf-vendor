<?php

use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\FrameworkBinding\Agavi\Provisioner\MigrationServiceProvisioner;

class Honeybee_Core_Migrate_CreateAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $migration_config = $this->loadMigrationConfig();
        $migration_config = $migration_config['targets'];

        $migration_target_name = $request_data->getParameter('target');
        if (!isset($migration_config[$migration_target_name])) {
            throw new RuntimeError(sprintf('Invalid migration target name given: %s', $migration_target_name));
        }

        $migration_target_config = $migration_config[$migration_target_name];
        $loader_config = $migration_target_config['migration_loader'];
        $loader_settings = $loader_config['settings'];

        if (!isset($loader_settings['directory'])) {
            throw new RuntimeError(
                'The configured MigrationLoader %s is not supported.'
                . 'Only loaders that expose a "directory" setting are allowed here.',
                $loader_config['class']
            );
        }

        $migration_dir = $loader_settings['directory'];
        if (!is_dir($migration_dir)) {
            mkdir($migration_dir, 0755);
        }

        if (!is_writable($migration_dir)) {
            throw new RuntimeError(
                sprintf("Missing FS write permissions in order to create migration inside: %s", $migration_dir)
            );
        }

        $this->setAttribute('migration_dir', $migration_dir);

        return 'Success';
    }

    protected function loadMigrationConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') .
            DIRECTORY_SEPARATOR .
            MigrationServiceProvisioner::MIGRATION_CONFIG_NAME
        );
    }
}
