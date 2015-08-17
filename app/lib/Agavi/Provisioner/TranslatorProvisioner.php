<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviContext;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\Ui\TranslatorInterface;

class TranslatorProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $agavi_context = AgaviContext::getInstance();

        $logger_name = $provisioner_settings->get('logger_name', 'translation');

        $state = [
            ':config' => $service_definition->getConfig(),
            ':tm' => $agavi_context->getTranslationManager(),
            ':logger' => $agavi_context->getLoggerManager()->getLogger($logger_name)->getPsr3Logger(),
        ];

        $this->di_container->define($service, $state)->share($service);

        $this->di_container->alias(TranslatorInterface::CLASS, $service);
    }
}
