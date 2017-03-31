<?php

namespace Honeygavi\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeygavi\Ui\OutputFormat\MediaTypeInfo;
use Honeygavi\Ui\OutputFormat\OutputFormat;
use Honeygavi\Ui\OutputFormat\OutputFormatMap;
use Honeygavi\Ui\OutputFormat\OutputFormatServiceInterface;

class OutputFormatServiceProvisioner extends AbstractProvisioner
{
    const CONFIG_NAME = 'output_formats.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $config = $this->loadConfig();

        $output_format_map = new OutputFormatMap();
        foreach ($config as $name => $output_format) {
            $output_format['media_type_info'] = new MediaTypeInfo($output_format['media_type_info']);
            $output_format_map->setItem($name, new OutputFormat($output_format));
        }

        $service = $service_definition->getClass();

        $state = [ ':output_format_map' => $output_format_map ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(OutputFormatServiceInterface::CLASS, $service);
    }

    protected function loadConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::CONFIG_NAME
        );
    }
}
