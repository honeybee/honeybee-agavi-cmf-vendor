<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

class AggregateRootTypeMapGenerator extends EntityTypeMapGenerator
{
    const TEMPLATE = 'ar_type_map.php.twig';

    const CONFIG_NAME = 'ar_type_map.php';

    protected function getConfigFileName()
    {
        return self::CONFIG_NAME;
    }

    protected function getTemplateName()
    {
        return self::TEMPLATE;
    }
}
