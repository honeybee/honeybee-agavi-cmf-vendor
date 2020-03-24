<?php

namespace Honeygavi\Util;

use Honeybee\Model\Aggregate\AggregateRootType;
use AgaviConfig;

class HoneybeeAgaviToolkit
{
    const CONFIG_PATH = 'config';

    const LIB_PATH = 'lib';

    const SCHEMA_PATH = 'entity_schema';

    protected static function generateTypePath(AggregateRootType $aggregate_root_type, $path)
    {
        return \sprintf(
            '%1$s%2$s%3$s_%4$s%2$s%5$s%2$s%6$s',
            AgaviConfig::get('core.modules_dir'),
            \DIRECTORY_SEPARATOR,
            $aggregate_root_type->getVendor(),
            $aggregate_root_type->getPackage(),
            $path,
            $aggregate_root_type->getName()
        );
    }

    public static function getTypeLibPath(AggregateRootType $aggregate_root_type)
    {
        return self::generateTypePath($aggregate_root_type, self::LIB_PATH);
    }

    public static function getTypeConfigPath(AggregateRootType $aggregate_root_type)
    {
        return self::generateTypePath($aggregate_root_type, self::CONFIG_PATH);
    }

    public static function getTypeSchemaPath(AggregateRootType $aggregate_root_type)
    {
        return \sprintf(
            '%s%s%s',
            self::generateTypePath($aggregate_root_type, self::CONFIG_PATH),
            \DIRECTORY_SEPARATOR,
            self::SCHEMA_PATH
        );
    }
}
