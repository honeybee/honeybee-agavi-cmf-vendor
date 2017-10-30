<?php

namespace Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\ServiceLocatorInterface;
use Honeygavi\Ui\Filter\ListFilterMap;
use Psr\Log\LoggerInterface;

class ListFilterService implements ListFilterServiceInterface
{
    const DEFAULT_FILTER_IMPLEMENTOR = ListFilter::CLASS;

    /**
     * @var ProjectionTypeMap $projection_type_map
     */
    protected $projection_type_map;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    public function __construct(ProjectionTypeMap $projection_type_map, LoggerInterface $logger)
    {
        $this->projection_type_map = $projection_type_map;
        $this->logger = $logger;
    }

    public function resolveAttributeFor($filter_name, $resource_type_variant_prefix, $attribute_path = '')
    {
        if (!$this->projection_type_map->hasKey($resource_type_variant_prefix)) {
            return null;
        }
        $resource_type = $this->projection_type_map->getItem($resource_type_variant_prefix);

        if (empty($attribute_path)) {
            // name is used as attribute path (just first-level attributes, as dots
            // can ambiguously be either storage-field- or attribute-path-separators)
            $attribute_path = current(explode('.', $filter_name));
        }

        return $resource_type->hasAttribute($attribute_path)
            ? $resource_type->getAttribute($attribute_path)
            : null;
    }

    public function buildMapFor(
        SettingsInterface $defined_list_filters,
        SettingsInterface $list_filter_values,
        $resource_type_variant_prefix = ''
    ) {
        $list_filter_map = new ListFilterMap;
        $this->loadFilterDefinitions(
            $list_filter_map,
            $defined_list_filters,
            $list_filter_values,
            $resource_type_variant_prefix
        );
        $this->loadUndefinedFilters(
            $list_filter_map,
            $defined_list_filters,
            $list_filter_values,
            $resource_type_variant_prefix
        );
        $this->checkConfigurationConflicts($list_filter_map);

        return $list_filter_map;
    }

    public function loadFilterDefinitions(
        ListFilterMap $list_filter_map,
        SettingsInterface $defined_list_filters,
        SettingsInterface $list_filter_values,
        $default_resource_type_variant_prefix = ''
    ) {
        foreach ($defined_list_filters as $filter_name => $settings) {
            $settings = $settings ?? new Settings;
            $filter_implementor = $settings->get('implementor', static::DEFAULT_FILTER_IMPLEMENTOR);
            $filter_value = $list_filter_values->get($filter_name);
            $filter_settings = $settings->toArray() + [ 'resource_type_variant_prefix' => $default_resource_type_variant_prefix ];

            $filter_attribute = $this->resolveAttributeFor(
                $filter_name,
                $filter_settings['resource_type_variant_prefix'],
                $settings->get('attribute_path')
            );

            $list_filter_map->setItem(
                $filter_name,
                new $filter_implementor(
                    $filter_name,
                    $filter_value,
                    $filter_attribute,
                    $filter_settings
                )
            );
        }
    }

    public function loadUndefinedFilters(
        ListFilterMap $list_filter_map,
        SettingsInterface $defined_list_filters,
        SettingsInterface $list_filter_values,
        $resource_type_variant_prefix = ''
    ) {
        $undefined_filters = array_filter(
            $list_filter_values->toArray(),
            function ($filter_name) use ($defined_list_filters) {
                return $defined_list_filters->has($filter_name) === false;
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($undefined_filters as $filter_name => $filter_value) {
            $list_filter = new ListFilter(
                $filter_name,
                $filter_value,
                $this->resolveAttributeFor($filter_name, $resource_type_variant_prefix),
                [ 'resource_type_variant_prefix' => $resource_type_variant_prefix ]
            );
            $list_filter_map->setItem($filter_name, $list_filter);
        }
    }

    public function checkConfigurationConflicts(ListFilterMap $list_filter_map)
    {
        $conflicting_names = $this->getConflictingConfigurationKeys($list_filter_map);

        if ($conflicting_names) {
            throw new RuntimeError(sprintf(
                "List filter configuration conflicts on following names: %s. Please, specify the 'config_key' setting.",
                join(', ', array_keys($conflicting_names))
            ));
        }
    }

    public function getConflictingConfigurationKeys(ListFilterMap $list_filter_map)
    {
        $config_keys = array_map(function ($filter) {
            return $filter->getSettings()->get('config_key');
        }, $list_filter_map->getItems());

        return array_filter(
            array_count_values($config_keys),
            function ($count) {
                return $count > 1;
            }
        );

        return false;
    }
}
