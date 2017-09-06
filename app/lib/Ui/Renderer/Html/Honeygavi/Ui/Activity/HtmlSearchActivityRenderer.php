<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Activity;

use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Activity\Activity;
use Honeygavi\Ui\Activity\ActivityMap;
use Honeygavi\Ui\Activity\Url;
use Honeygavi\Ui\Filter\ListFilter;
use Honeygavi\Ui\Filter\ListFilterMap;

class HtmlSearchActivityRenderer extends HtmlActivityRenderer
{
    protected $type;

    protected function validate()
    {
        parent::validate();

        if ($this->getPayload('resource') instanceof ProjectionInterface) {
            throw new RuntimeError('Payload "resource" must be provided and must implement: ' . ProjectionInterface::CLASS);
        }
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/activity/search_form.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        if ($this->getOption('enable_list_filters', true)) {
            $list_filter_map = $this->getListFilterMap();
            if (!$list_filter_map->isEmpty()) {
                // add active list filters to search form
                $params['form_parameters'] = (array)$params['form_parameters'];
                // render list filters
                $params['rendered_list_filters'] = $this->renderListFilters($list_filter_map);
                // render list filters control activity map
                $params['rendered_list_filters_control'] = $this->renderListFiltersControl($list_filter_map);
            }
        }

        return $params;
    }

    protected function getListFilterMap()
    {
        $defined_list_filters = $this->getOption('defined_list_filters', []);
        $list_filters_values = $this->getOption('list_filters_values', []);
        $list_filter_map = new ListFilterMap();

        // defined filters
        foreach ($defined_list_filters as $filter_name => $settings) {
            $settings = new Settings((array)$settings);

            $filter_implementor = $settings->get('implementor', ListFilter::CLASS);
            $filter_value = $list_filters_values[$filter_name] ?? null;

            $list_filter_map->setItem(
                $filter_name,
                new $filter_implementor(
                    $filter_name,
                    $filter_value,
                    $this->resolveFilterAttribute($filter_name, $settings->get('attribute_path'))
                )
            );
        }
        // undefined filters
        foreach ($list_filters_values as $filter_name => $filter_value) {
            if ($list_filter_map->hasKey($filter_name)) {
                continue;
            }
            $list_filter_map->setItem(
                $filter_name,
                new ListFilter(
                    $filter_name,
                    $filter_value,
                    $this->resolveFilterAttribute($filter_name)
                )
            );
        }

        return $list_filter_map;
    }

    protected function resolveFilterAttribute($filter_name, $attribute_path = null)
    {
        $type = $this->getPayload('resource')->getType();

        if (!empty($attribute_path)) {
            $attribute_path = $attribute_path;
        } elseif (strpos($filter_name, '.') === false) {
            // filter name can be used as attribute path (dots not supported)
            $attribute_path = $filter_name;
        } else {
            $attribute_path = '';
        }

        return $type->hasAttribute($attribute_path)
            ? $type->getAttribute($attribute_path)
            : null;
    }

    protected function renderListFilters(ListFilterMap $list_filter_map, $render_settings = [])
    {
        $view_scope = $this->getOption('view_scope', 'missing.view_scope');

        $renderer_config = $this->view_config_service->getRendererConfig(
            $view_scope,
            $this->output_format,
            'list_filters'
        );

        return $this->renderer_service->renderSubject(
            $list_filter_map,
            $this->output_format,
            $renderer_config,
            [ 'resource' => $this->getPayload('resource') ],
            new Settings(
                array_replace_recursive(
                    [ 'view_scope' => $view_scope ],
                    $render_settings
                )
            )
        );
    }

    protected function renderListFiltersControl(ListFilterMap $list_filter_map)
    {
        $view_scope = $this->getOption('view_scope', 'missing.view_scope');

        $renderer_config = $this->view_config_service->getRendererConfig(
            $view_scope,
            $this->output_format,
            'list_filters_control'
        );

        return $this->renderer_service->renderSubject(
            $this->buildListFiltersActivityMap($list_filter_map),
            $this->output_format,
            null,
            [],
            new Settings(
                [
                    'view_scope' => $view_scope,
                    'as_dropdown' => true,
                    'emphasized' => true,
                    'css' => 'hb-list-filters-control activity-map',
                    // 'name' => 'list-filters-control',    // @todo fix css support for activity-map name
                    'default_description' => $this->_('collection.list_filters.description'),
                    'dropdown_label' => $this->_('collection.add_list_filter')
                ]
            )
        );
    }

    protected function buildListFiltersActivityMap(ListFilterMap $list_filter_map)
    {
        $list_filters_activity_map = new ActivityMap();
        foreach ($list_filter_map as $list_filter) {
            $activity_name = 'list_filter_' . $list_filter->getName();
            $list_filters_activity_map->setItem(
                $activity_name,
                new Activity([
                    'name' => $activity_name,
                    'scope' => 'list_filters',
                    'url' => Url::createUri(sprintf('#%s', $list_filter->getName())),
                    'label' => sprintf('%s.label', $activity_name),
                    'description' => sprintf('%s.description', $activity_name),
                    'verb' => 'read',
                    'settings' => new Settings
                ])
            );
        }

        return $list_filters_activity_map;
    }
}
