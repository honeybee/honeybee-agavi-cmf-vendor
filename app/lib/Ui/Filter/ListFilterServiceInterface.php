<?php

namespace Honeygavi\Ui\Filter;

use Honeybee\Infrastructure\Config\SettingsInterface;

interface ListFilterServiceInterface
{
    public function resolveAttributeFor($filter_name, $resource_type_variant_prefix, $attribute_path = '');

    public function buildMapFor(
        SettingsInterface $defined_list_filters,
        array $list_filter_values,
        $resource_type_variant_prefix = ''
    );
}
