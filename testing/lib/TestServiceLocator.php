<?php

namespace Honeybee\Tests;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\ServiceLocator;

class TestServiceLocator extends ServiceLocator
{
    public function prepareService($service_key, array $state = [])
    {
        if (!$this->service_map->hasKey($service_key)) {
            throw new RuntimeError(sprintf('No service found for given service-key: "%s".', $service_key));
        }

        $service_definition = $this->service_map->getItem($service_key);

        return $this->di_container->make($service_definition->getClass(), $state);
    }
}
