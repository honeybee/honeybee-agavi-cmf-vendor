<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

class ProjectionTypeMapGenerator extends EntityTypeMapGenerator
{
    const TEMPLATE = 'projection_type_map.php.twig';

    const CONFIG_NAME = 'projection_type_map.php';

    protected function getConfigFileName()
    {
        return self::CONFIG_NAME;
    }

    protected function getTemplateName()
    {
        return self::TEMPLATE;
    }
}
