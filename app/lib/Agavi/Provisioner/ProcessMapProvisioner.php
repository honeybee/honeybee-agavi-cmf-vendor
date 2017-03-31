<?php

namespace Honeygavi\Agavi\Provisioner;

use AgaviContext;
use AgaviConfig;
use AgaviConfigCache;
use Auryn\Injector as DiContainer;
use Honeygavi\Agavi\Provisioner\AbstractProvisioner;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeygavi\ProcessManager\Process;

class ProcessMapProvisioner extends AbstractProvisioner
{
    const PROCESS_CONFIG_FILE = 'process.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $process_map_config = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::PROCESS_CONFIG_FILE,
            AgaviContext::getInstance()->getName()
        );
        $process_map_class = $service_definition->getClass();
        $this->di_container->share($process_map_class)->delegate(
            $process_map_class,
            function (DiContainer $di_container) use ($process_map_class, $process_map_config) {
                $process_map = new $process_map_class();
                foreach ($process_map_config as $process_name => $process_config) {
                    $builder_settings = $process_config['builder']['settings'];
                    if (!isset($builder_settings['name'])) {
                        $builder_settings['name'] = $process_name;
                    }

                    $state_machine_builder = $di_container->make(
                        $process_config['builder']['class'],
                        [ ':options' =>  $builder_settings ]
                    );

                    $process_implementor = isset($process_config['class']) ? $process_config['class'] : Process::CLASS;
                    $process_map->setItem(
                        $process_name,
                        $di_container->make(
                            $process_implementor,
                            [ ':name' => $process_name,  ':state_machine' => $state_machine_builder->build()
                            ]
                        )
                    );
                }

                return $process_map;
            }
        );
    }
}
