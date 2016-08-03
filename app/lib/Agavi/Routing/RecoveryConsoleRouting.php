<?php

namespace Honeybee\FrameworkBinding\Agavi\Routing;

use AgaviConsoleRouting;
use AgaviConfigCache;
use RuntimeException;

class RecoveryConsoleRouting extends AgaviConsoleRouting
{
    protected function loadConfig()
    {
        $recovery_routing_cfg = $this->getParameter('routing_config');

        // allow missing routing.xml when routing is not enabled
        if (!is_readable($recovery_routing_cfg)) {
            throw new RuntimeException("Emergency routing file not found: " . $recovery_routing_cfg);
        }

        $this->importRoutes(
            unserialize(
                file_get_contents(
                    AgaviConfigCache::checkConfig($recovery_routing_cfg, $this->context->getName())
                )
            )
        );
    }
}
