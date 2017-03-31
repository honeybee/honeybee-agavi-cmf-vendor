<?php

namespace Honeygavi\Provisioner;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Expression\ExpressionServiceInterface;
use Honeybee\ServiceDefinitionInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionServiceProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $state = [ ':expression_language' => new ExpressionLanguage() ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(ExpressionServiceInterface::CLASS, $service);
    }
}
