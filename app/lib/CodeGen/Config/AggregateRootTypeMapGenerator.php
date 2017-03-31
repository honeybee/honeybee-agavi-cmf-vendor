<?php

namespace Honeygavi\CodeGen\Config;

class AggregateRootTypeMapGenerator extends EntityTypeMapGenerator
{
    const TEMPLATE = 'aggregate_root_type_map.php.twig';

    const CONFIG_NAME = 'aggregate_root_type_map.php';

    protected function getConfigFileName()
    {
        return self::CONFIG_NAME;
    }

    protected function getTemplateName()
    {
        return self::TEMPLATE;
    }
}
