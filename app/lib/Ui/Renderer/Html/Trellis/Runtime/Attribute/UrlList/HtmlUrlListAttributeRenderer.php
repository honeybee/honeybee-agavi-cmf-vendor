<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\UrlList;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\UrlList\UrlListAttribute;

class HtmlUrlListAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        if (StringToolkit::endsWith($view_scope, 'collection')) {
            return $this->output_format->getName() . '/attribute/url-list/as_itemlist_item_cell.twig';
        }

        return $this->output_format->getName() . '/attribute/url-list/as_input.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['grouped_field_name'] = $params['grouped_field_name'] . '[]';

        $params['maxlength'] = $this->getOption(
            'maxlength',
            $this->attribute->getOption(UrlListAttribute::OPTION_MAX_LENGTH)
        );

        $display_inputs = (int)$this->getOption('display_inputs', -1);
        $params['missing_inputs'] = 0;
        if ($display_inputs > -1) {
            $missing_inputs = $display_inputs - count($params['attribute_value']);
            if ($missing_inputs > 0) {
                $params['missing_inputs'] = $missing_inputs;
            }
        }

        return $params;
    }

    protected function determineAttributeValue($attribute_name, $default_value = '')
    {
        $urls = [];

        if ($this->hasOption('value')) {
            return (array)$this->getOption('value', $default_value);
        }

        $expression = $this->getOption('expression');
        if (!empty($expression)) {
            $urls = $this->evaluateExpression($expression);
        } else {
            $urls = $this->getPayload('resource')->getValue($attribute_name);
        }

        $urls = is_array($urls) ? $urls : [ $urls ];

        return $urls;
    }

    protected function getWidgetOptions()
    {
        $widget_options = parent::getWidgetOptions();

        $widget_options['min_count'] = $this->getMinCount($this->isRequired());
        $widget_options['max_count'] = $this->getMaxCount();

        return $widget_options;
    }

    protected function getMinCount($is_required = false)
    {
        $min_count = $this->getOption(
            'min_count',
            $this->attribute->getOption(UrlListAttribute::OPTION_MIN_COUNT)
        );

        if (!is_numeric($min_count) && $is_required) {
            $min_count = 1;
        }

        return $min_count;
    }

    protected function getMaxCount()
    {
        return $this->getOption(
            'max_count',
            $this->attribute->getOption(UrlListAttribute::OPTION_MAX_COUNT)
        );
    }

    protected function isRequired()
    {
        $is_required = parent::isRequired();

        $url_list = $this->determineAttributeValue($this->attribute->getName());

        // check options against actual value
        $items_number = count($url_list);
        $min_count = $this->getMinCount($is_required);

        if (is_numeric($min_count) && $items_number < $min_count) {
            $is_required = true;
        }

        return $is_required;
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', '');
    }
}
