<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Auryn\Injector as DiContainer;
use Honeybee\Common\Error\ConfigError;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorServiceInterface;
use Honeybee\Infrastructure\Filesystem\FilesystemServiceInterface;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\ServiceDefinitionInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;

class FilesystemServiceProvisioner extends AbstractProvisioner
{
    const FILESYSTEMS_CONFIG_NAME = 'filesystems.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $config = $this->loadFilesystemsConfig();

        $factory_delegate = function (
            AggregateRootTypeMap $art_map,
            ConnectorServiceInterface $connector_service
        ) use (
            $service,
            $config
) {
            $filesystems = [];
            $connectors = [];
            $schemes = [];

            // check all configured filesystem connectors (they wrap a Filesystem instance with a specific adapter)
            foreach ($config as $scheme => $connector_name) {
                $connectors[$scheme] = $connector_service->getConnector($connector_name);
                $schemes[$scheme] = $connector_name;
            }

            if (!array_key_exists(FilesystemServiceInterface::SCHEME_FILES, $schemes)) {
                throw new ConfigError(
                    sprintf(
                        'There is no filesystem connector registered for scheme "%s". Please check the config: %s',
                        FilesystemServiceInterface::SCHEME_FILES,
                        self::FILESYSTEMS_CONFIG_NAME
                    )
                );
            }

            if (!array_key_exists(FilesystemServiceInterface::SCHEME_TEMPFILES, $schemes)) {
                throw new ConfigError(
                    sprintf(
                        'There is no filesystem connector registered for scheme "%s". Please check the config: %s',
                        FilesystemServiceInterface::SCHEME_TEMPFILES,
                        self::FILESYSTEMS_CONFIG_NAME
                    )
                );
            }

            // reuse configured schemes for aggregate root specific file schemes
            foreach ($art_map->getKeys() as $art_prefix) {
                $files_scheme = $art_prefix .'.'. FilesystemServiceInterface::SCHEME_FILES;
                $tempfiles_scheme = $art_prefix .'.'. FilesystemServiceInterface::SCHEME_TEMPFILES;
                if (!array_key_exists($files_scheme, $schemes)) {
                    $schemes[$files_scheme] = $connectors[FilesystemServiceInterface::SCHEME_FILES]->getName();
                }

                if (!array_key_exists($tempfiles_scheme, $schemes)) {
                    $schemes[$tempfiles_scheme] = $connectors[FilesystemServiceInterface::SCHEME_TEMPFILES]->getName();
                }
            }

            // get actual Filesystem instances for each scheme (they are configured and ready to use after this)
            foreach ($schemes as $scheme => $connector_name) {
                $filesystem = $connector_service->getConnection($connector_name);
                if (!$filesystem instanceof FilesystemInterface) {
                    throw new ConfigError(
                        sprintf(
                            'Filesystem connector for scheme "%s" must be an instance of: %s',
                            $scheme,
                            FilesystemInterface::CLASS
                        )
                    );
                }
                $filesystems[$scheme] = $filesystem;
            }

            $mount_manager = new MountManager($filesystems);
            return new $service($mount_manager, $schemes);
        };

        $this->di_container
            ->delegate($service, $factory_delegate)
            ->share($service)
            ->alias(FilesystemServiceInterface::CLASS, $service);
    }

    protected function loadFilesystemsConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::FILESYSTEMS_CONFIG_NAME
        );
    }
}
