<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Activity;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Activity\Activity;
use Honeygavi\Ui\Activity\ActivityMap;
use Honeygavi\Ui\Activity\Url;
use Honeygavi\Ui\Filter\ListFilter;
use Honeygavi\Ui\Filter\ListFilterMap;

class HtmlSearchActivityRenderer extends HtmlActivityRenderer
{
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

        $params['form_parameters'] = (array)$params['form_parameters'];
        $params = array_replace($params, $this->getListFilterParams());

        return $params;
    }

    protected function getListFilterParams()
    {
        $list_filter_params = [];

        if ($this->getOption('enable_list_filters', true)) {
            $list_filter_map = $this->list_filter_service->buildMapFor(
                $this->getOption('defined_list_filters') ?? new Settings,
                (array) $this->getOption('list_filters_values'),
                $this->getPayload('resource')->getType()->getVariantPrefix()
            );

            if (!$list_filter_map->isEmpty()) {
                $list_filter_params['rendered_list_filters'] = $this->renderListFilters($list_filter_map);
                $list_filter_params['rendered_list_filters_control'] = $this->renderListFiltersControl($list_filter_map);
            }
        }

        return $list_filter_params;
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
                    'more_css' => 'hb-list-filter--prevent-autotoggle',
                    // 'name' => 'list-filters-control',    // @todo fix css support for activity-map name
                    'default_description' => $this->_('collection.list_filters.description', null, null, null, ''),
                    'dropdown_label' => $this->_('collection.add_list_filter', null, null, null, '')
                ]
            )
        );
    }

    protected function buildListFiltersActivityMap(ListFilterMap $list_filter_map)
    {
        $list_filters_activity_map = new ActivityMap();
        foreach ($list_filter_map as $list_filter) {
            $config_key = $list_filter->getSettings()->get('config_key');
            $activity_name = 'list_filter_' . $config_key;
            $list_filters_activity_map->setItem(
                $activity_name,
                new Activity([
                    'name' => $activity_name,
                    'scope' => 'list_filters',
                    'url' => Url::createUri(sprintf('#%s', $config_key)),
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
