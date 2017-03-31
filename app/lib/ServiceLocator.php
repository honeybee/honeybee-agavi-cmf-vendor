<?php

namespace Honeygavi;

use Auryn\Injector as DiContainer;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\EntityInterface;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\ServiceDefinitionMap;
use Honeybee\ServiceLocatorInterface;
use ReflectionClass;

final class ServiceLocator implements ServiceLocatorInterface
{
    private static $service_key_map = [
        'activity_service' => 'honeybee.ui',
        'translator' => 'honeybee.ui',
        'url_generator' => 'honeybee.ui',
        'template_renderer' => 'honeybee.ui',
        'output_format_service' => 'honeybee.ui',
        'navigation_service' => 'honeybee.ui',
        'renderer_service' => 'honeybee.ui',
        'view_config_service' => 'honeybee.ui',
        'view_template_service' => 'honeybee.ui',
        'task_service' => 'honeybee.model'
    ];

    private $service_locator;

    public function __construct(DiContainer $di_container, ServiceDefinitionMap $service_map)
    {
        $this->di_container = $di_container;
        $this->service_map = $service_map;
        $this->service_locator = new \Honeybee\ServiceLocator($di_container, $service_map);
    }

    public function get($service_key)
    {
        return $this->service_locator->get($service_key);
    }

    public function has($service_key)
    {
        return $this->service_locator->has($service_key);
    }

    public function make(string $implementor, array $state = [])
    {
        return $this->service_locator->make($implementor, $state);
    }

    public function __call($method, array $args)
    {
        if (preg_match('/^get(\w+)$/', $method, $matches)) {
            $service_name = StringToolkit::asSnakeCase($matches[1]);
            switch ($service_name) {
                case 'projection_type_map':
                    return $this->make(ProjectionTypeMap::CLASS);
                case 'aggregate_root_type_map':
                    return $this->make(AggregateRootTypeMap::CLASS);
                default:
                    if (isset(self::$service_key_map[$service_name])) {
                        return $this->get(self::$service_key_map[$service_name].'.'.$service_name);
                    }
                    return $this->service_locator->$method();
            }
        }
    }
}
