<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Auryn\Injector as DiContainer;
use Honeybee\Common\Error\ConfigError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorServiceInterface;
use Honeygavi\Mail\MailServiceInterface;
use Honeybee\ServiceDefinitionInterface;
use Psr\Log\LoggerInterface;
use Swift_Mailer;

class MailServiceProvisioner extends AbstractProvisioner
{
    const CONFIG_FILE_NAME = 'mail.xml';

    protected $connector_service;

    public function __construct(
        DiContainer $di_container,
        LoggerInterface $logger,
        ConnectorServiceInterface $connector_service
    ) {
        parent::__construct($di_container, $logger);

        $this->connector_service = $connector_service;
    }

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $config = $this->loadConfig();

        if (!isset($config[MailServiceInterface::DEFAULT_MAILER_NAME])) {
            throw new ConfigError(
                sprintf(
                    'The "%s" config file needs to specify a default mailer config under the key "%s".',
                    self::CONFIG_FILE_NAME,
                    MailServiceInterface::DEFAULT_MAILER_NAME
                )
            );
        }

        $connector_name = $provisioner_settings->get('connection', 'Default.Mailer');

        $swift_mailer = $this->connector_service->getConnection($connector_name);
        if (!$swift_mailer instanceof Swift_Mailer) {
            throw new ConfigError(
                sprintf(
                    'MailService connector "%s" must be an instance of: %s',
                    $connector_name,
                    Swift_Mailer::CLASS
                )
            );
        }

        $state = [
            ':mailer_configs' => new ArrayConfig($config),
            ':mailer' => $swift_mailer
        ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(MailServiceInterface::CLASS, $service);
    }

    protected function loadConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::CONFIG_FILE_NAME
        );
    }
}
