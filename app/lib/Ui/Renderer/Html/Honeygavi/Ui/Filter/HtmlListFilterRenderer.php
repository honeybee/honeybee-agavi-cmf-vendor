<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Ui\Filter\ListFilterInterface;
use Honeygavi\Ui\Renderer\Renderer;

class HtmlListFilterRenderer extends Renderer
{
    const STATIC_TRANSLATION_PATH = 'list_filters';

    protected $list_filter;
    protected $attribute;

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/pick_template.twig';
    }

    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof ListFilterInterface) {
            throw new RuntimeError('Payload "subject" must be an instance of: ' . ListFilterInterface::CLASS);
        }

        $this->list_filter = $this->getPayload('subject');
        $this->attribute = $this->getPayload('attribute', $this->list_filter->getAttribute());
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        // params for pick-template
        $params['resource_type_prefix'] = $this->getPayload('resource')->getType()->getPrefix();
        $params['attribute_name'] = 'missing';
        $params['attribute_type_name'] = 'missing';
        if ($this->attribute) {
            $params['attribute_name'] = $this->attribute->getName();
            $params['attribute_type_name'] = $this->name_resolver->resolve($this->attribute);
        }

        $params['filter_value_translation'] = $this->getTranslatedFilterValue($this->list_filter);
        $params['filter_value'] = $this->list_filter->getCurrentValue();
        $params['filter_name'] = $this->list_filter->getName();
        $params['filter_id'] = $this->list_filter->getId();   // we don't want dots
        $params['html_attributes'] = $this->getOption('html_attributes', []);

        $params['widget_enabled'] = $this->isWidgetEnabled();
        $params['widget_options'] = $this->getWidgetOptions();
        if ($this->hasOption('tabindex')) {
            $params['tabindex'] = $this->getOption('tabindex');
        }

        $params['css_prefix'] = $this->getOption('css_prefix', 'hb-list-filter');
        $css = (string)$this->getOption('css', '');
        if ($this->attribute) {
            $css .= sprintf(' %s_%s', $params['css_prefix'], $params['attribute_name']);
        }
        if ($this->isWidgetEnabled()) {
            $css .= sprintf(' jsb_ %s', $this->getWidgetImplementor());
        }
        $params['css'] = $css;

        return $params;
    }

    protected function getTranslatedFilterValue()
    {
        $filter_value = $this->list_filter->getCurrentValue();
        $filter_value_key = sprintf('%s.value_%s', $this->list_filter->getName(), $filter_value);

        return $this->_($filter_value_key, null, null, null, '') ? $filter_value_key : $filter_value;
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

        return array_replace_recursive($widget_options, (array)$this->getOption('widget_options', []));
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', null);
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
