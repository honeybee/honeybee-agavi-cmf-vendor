<?php

namespace Honeybee\Tests\Mock;

use AgaviConfig;
use AgaviConfigCache;
use AgaviContext;
use Auryn\Injector as DiContainer;
use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Agavi\ServiceProvisioner;

class TestServiceProvisioner extends ServiceProvisioner
{
    public function __construct(DiContainer $di_container)
    {
        $this->di_container = $di_container;

        $this->service_map = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::SERVICES_CONFIG_NAME,
            AgaviContext::getInstance()->getName()
        );

        $this->aggregate_root_type_map = $this->loadTypeMap(self::AGGREGATE_ROOT_TYPE_MAP_CONFIG_NAME);
        $this->projection_type_map = $this->loadTypeMap(self::PROJECTION_TYPE_MAP_CONFIG_NAME);

        $this->di_container->share($this->service_map);
        $this->di_container->share($this->aggregate_root_type_map);
        $this->di_container->share($this->projection_type_map);

        $this->provisioned_services = [];
    }

    // @todo better fallback mechanism
    protected function loadTypeMap($filename)
    {
        $ar_type_map_path = implode(
            DIRECTORY_SEPARATOR,
            [ AgaviConfig::get('core.testing_dir'), 'config', $filename ]
        );
        if (is_readable($ar_type_map_path)) {
            return include $ar_type_map_path;
        } else {
            $ar_type_map_alt_path = implode(
                DIRECTORY_SEPARATOR,
                [ AgaviConfig::get('core.config_dir'), 'includes', $filename ]
            );
            if (is_readable($ar_type_map_alt_path)) {
                return include $ar_type_map_alt_path;
            } else {
                throw new RuntimeError('Type map file could not be loaded: ' . $filename);
            }
        }
    }
}
