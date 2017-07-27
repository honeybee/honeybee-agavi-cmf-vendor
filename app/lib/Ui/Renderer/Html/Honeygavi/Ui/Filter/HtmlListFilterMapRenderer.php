<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\EntityInterface;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Filter\ListFilter;
use Honeygavi\Ui\Filter\ListFilterMap;
use Honeygavi\Ui\Renderer\Renderer;

class HtmlListFilterMapRenderer extends Renderer
{
    const STATIC_TRANSLATION_PATH = 'list_filters';

    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof ListFilterMap) {
            throw new RuntimeError('Payload "subject" must be an instance of: ' . ListFilterMap::CLASS);
        }

        if (!$this->getPayload('resource') instanceof EntityInterface) {
            throw new RuntimeError('Payload "resource" must implement: ' . EntityInterface::CLASS);
        }
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $list_filter_map = $this->getPayload('subject');
        $custom_list_filters = $this->getOption('custom_list_filters');

        $params['rendered_list_filters'] = $this->renderListFilters($list_filter_map);
        $params['rendered_list_filters_templates'] = $custom_list_filters instanceof ListFilterMap
            ? $this->renderListFilters($custom_list_filters)
            : [];
        $params['list_filters'] = array_map(
            function ($filter) use ($list_filter_map) {
                return $filter->toArray();
            },
            $list_filter_map->toArray()
        );

        $css = sprintf(
            (string)$this->getOption('css', '')
        );
        if ($this->isWidgetEnabled()) {
            $css .= sprintf(' jsb_ %s', $this->getWidgetImplementor());
        }
        $params['widget_enabled'] = $this->isWidgetEnabled();
        $params['widget_options'] = $this->getWidgetOptions();
        $params['css'] = $css;

        return $params;
    }

    protected function renderListFilters(ListFilterMap $list_filter_map)
    {
        $view_scope = $this->getOption('view_scope');
        $rendered_filters = [];

        foreach ($list_filter_map as $list_filter) {
            $renderer_config = $this->view_config_service->getRendererConfig(
                $view_scope,
                $this->output_format,
                $list_filter
            );
            $render_settings = new Settings([
                'view_scope' => $view_scope,
                'value' => $list_filter->getCurrentValue() // ease resuse of attribute-renderers
            ]);

            $rendered_filters[$list_filter->getName()] = $this->renderer_service->renderSubject(
                $list_filter,
                $this->output_format,
                $renderer_config,
                [
                    'resource' => $this->getPayload('resource'),
                    'attribute' => $list_filter->getAttribute()     // ease resuse of attribute-renderers
                ],
                $render_settings
            );
        }

        return $rendered_filters;
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/list-filters.twig';
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/ListFilters');
    }

    protected function isWidgetEnabled()
    {
        return (bool)$this->getOption('widget_enabled', $this->getWidgetImplementor() !== null);
    }

    protected function getWidgetOptions()
    {
        $widget_options = [];
        if ($this->hasOption('tabindex')) {
            $widget_options['tabindex'] = $this->getOption('tabindex');
        }

        return array_replace_recursive(
            $widget_options,
            [ 'translations' => $this->getWidgetTranslations()],
            (array)$this->getOption('widget_options', [])
        );
    }

    protected function getWidgetTranslations()
    {
        $translations = [
            'list_filters_clear' => $this->_('list_filters_clear'),
            'list_filters_clear.description' => $this->_('list_filters_clear.description')
        ];

        $filter_translation_keys = [
            'filter_%s.quick_label', 'quick_label',
            'filter_%s.quick_label.title', 'quick_label.title'
        ];
        foreach ($this->getPayload('subject') as $list_filter) {
            foreach ($filter_translation_keys as $key) {
                $key = sprintf($key, $list_filter->getId());
                if ($filter_translation = $this->_($key, null, null, null, '')) {
                    $translations[$key] = $filter_translation;
                }
            }
        }

        return $translations;
    }

    /**
     * Default translation domain for activity maps follows this fallback sequence:
     *  - 'view_scope' option
     *  - application translation domain
     *
     * To override with a custom value pass to the renderer the 'translation_domain' option
     */
    protected function getDefaultTranslationDomain()
    {
        $view_scope = $this->getOption('view_scope');

        if (empty($view_scope)) {
            $translation_domain_prefix = parent::getDefaultTranslationDomain();
        } else {
            // convention on view_scope value: the first 3 parts = vendor.package.resource_type
            $view_scope_parts = explode('.', $view_scope);
            $translation_domain_prefix = implode('.', array_slice($view_scope_parts, 0, 3));
        }

        $translation_domain = sprintf(
            '%s.%s',
            $translation_domain_prefix,
            self::STATIC_TRANSLATION_PATH
        );

        return $translation_domain;
    }
}
