<?php

namespace Honeygavi\Tests\Mock;

use Auryn\Injector as DiContainer;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\ServiceDefinitionMap;
use Honeygavi\ServiceLocator;
use Honeybee\ServiceLocatorInterface;

class TestServiceLocator implements ServiceLocatorInterface
{
    private $di_container;

    private $service_map;

    private $service_locator;

    public function __construct(DiContainer $di_container, ServiceDefinitionMap $service_map)
    {
        $this->di_container = $di_container;
        $this->service_map = $service_map;
        $this->service_locator = new ServiceLocator($di_container, $service_map);
    }

    public function get($service_key)
    {
        return $this->service_locator->get($service_key);
    }

    public function has($service_key)
    {
        return $this->service_locator->has($service_key);
    }

    public function __call($method, array $args)
    {
        return $this->service_locator->$method();
    }

    public function make($implementor, array $state = [])
    {
        return $this->service_locator->make($implementor, $state);
    }

    // Extra util methods used by tests.

    public function getInjector()
    {
        return $this->di_container;
    }

    public function prepareService($service_key, array $state = [])
    {
        if (!$this->service_map->hasKey($service_key)) {
            throw new RuntimeError(sprintf('No service found for given service-key: "%s".', $service_key));
        }
        $service_definition = $this->service_map->getItem($service_key);
        return $this->di_container->make($service_definition->getClass(), $state);
    }
}
