<?php

use Honeybee\Common\Error\RuntimeError;
use Honeygavi\App\Base\Action;
use Honeygavi\Provisioner\FixtureServiceProvisioner;

class Honeybee_Core_Fixture_CreateAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $fixture_config = $this->loadFixtureConfig();
        $fixture_config = $fixture_config['targets'];
        $fixture_target_name = $request_data->getParameter('target');
        if (!isset($fixture_config[$fixture_target_name])) {
            throw new RuntimeError(sprintf('Invalid fixture target name given: %s', $fixture_target_name));
        }

        $fixture_target_config = $fixture_config[$fixture_target_name];
        $loader_config = $fixture_target_config['fixture_loader'];
        $loader_settings = $loader_config['settings'];

        if (!isset($loader_settings['directory'])) {
            throw new RuntimeError(
                'The configured FixtureLoader %s is not supported.'
                . 'Only loaders that expose a "directory" setting are allowed here.',
                $loader_config['class']
            );
        }

        $fixture_dir = $loader_settings['directory'];
        if (!is_dir($fixture_dir)) {
            mkdir($fixture_dir, 0755);
        }

        if (!is_writable($fixture_dir)) {
            throw new RuntimeError(
                sprintf("Missing FS write permissions in order to create fixture inside: %s", $fixture_dir)
            );
        }

        $this->setAttribute('fixture_dir', $fixture_dir);

        return 'Success';
    }

    protected function loadFixtureConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') .
            DIRECTORY_SEPARATOR .
            FixtureServiceProvisioner::FIXTURE_CONFIG_NAME
        );
    }
}
