<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Ui\Filter\ListFilterInterface;
use Honeygavi\Ui\Renderer\Renderer;
use Trellis\Runtime\Attribute\Attribute;

class HtmlListFilterRenderer extends Renderer
{
    const STATIC_TRANSLATION_PATH = 'list_filters';

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/pick_template.twig';
    }

    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof ListFilterInterface) {
            throw new RuntimeError('Payload "subject" must be an instance of: ' . ListFilterInterface::CLASS);
        }
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $list_filter = $this->getPayload('subject');
        $list_filter_attribute = $list_filter->getAttribute();

        $params['filter_name'] = $list_filter->getName();
        $params['filter_value'] = $list_filter->getCurrentValue();
        $params['attribute_name'] = $list_filter_attribute->getName();
        $params['resource_type_prefix'] = $this->getPayload('resource')->getType()->getPrefix();
        $params['attribute_type_name'] = $list_filter_attribute ? $this->name_resolver->resolve($list_filter_attribute) : 'missing';

        $params['widget_enabled'] = $this->isWidgetEnabled();
        $params['widget_options'] = $this->getWidgetOptions();
        if ($this->hasOption('tabindex')) {
            $params['tabindex'] = $this->getOption('tabindex');
        }

        $params['css_prefix'] = $this->getOption('css_prefix', 'list-filter');
        $css = sprintf(
            '%s_%s %s',
            $params['css_prefix'],
            $params['attribute_name'],
            (string)$this->getOption('css', '')
        );
        if ($this->isWidgetEnabled()) {
            $css .= sprintf(' jsb_ %s', $this->getWidgetImplementor());
        }
        $params['css'] = $css;

        return $params;
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

    protected function getDefaultTranslationDomain()
    {
        return sprintf(
            '%s.%s',
            $this->getOption('view_scope'),
            self::STATIC_TRANSLATION_PATH
        );
    }
}
