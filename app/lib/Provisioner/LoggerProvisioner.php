<?php

namespace Honeygavi\Provisioner;

use AgaviContext;
use Auryn\Injector as DiContainer;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Psr\Log\LoggerInterface;
use Trellis\Common\Object;

class LoggerProvisioner extends Object implements ProvisionerInterface
{
    protected $di_container;

    public function __construct(DiContainer $di_container)
    {
        $this->di_container = $di_container;
    }

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $logger_manager = AgaviContext::getInstance()->getLoggerManager();

        $logger = $logger_manager->getLogger()->getPsr3Logger();

        $service = $service_definition->getClass();

        $this->di_container->share($logger)->alias(LoggerInterface::CLASS, $service);
    }
}
