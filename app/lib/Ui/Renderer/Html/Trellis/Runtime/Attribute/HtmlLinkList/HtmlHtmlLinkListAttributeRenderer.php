<?php

namespace Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlLinkList;

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\HtmlLink\HtmlLink;

class HtmlHtmlLinkListAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
            return $this->output_format->getName() . '/attribute/html-link-list/as_input.twig';
        }

        return $this->output_format->getName() . '/attribute/html-link-list/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['empty_htmllink'] = new HtmlLink([]);

        $params['htmllink_widget'] = $this->getWidgetCss(
            $this->getHtmlLinkWidgetImplementor(),
            $params['is_embedded']
        );
        $params['htmllink_widget_options'] = array_merge(
            [
                'field_name' => $params['field_name']
            ],
            (array)$this->getOption('htmllink_widget_options', [])
        );

        $params['widget'] = $this->getWidgetImplementor();
        $params['widget_options'] = array_merge(
            [
                'field_name' => $params['field_name'],
                'field_id' => $params['field_id'],
                'grouped_base_path' => $params['grouped_base_path'],
            ],
            (array)$this->getOption('widget_options', [])
        );
        return $params;
    }

    protected function determineAttributeValue($attribute_name)
    {
        $value = [];

        if ($this->hasOption('value')) {
            return (array)$this->getOption('value', []);
        }

        $expression = $this->getOption('expression');
        if (!empty($expression)) {
            $value = $this->evaluateExpression($expression);
        } else {
            $value = $this->getPayload('resource')->getValue($attribute_name);
        }

        if ($value === $this->attribute->getNullValue()) {
            return [];
        } else {
            return $value;
        }
    }

    protected function getDefaultTranslationKeys()
    {
        return array_merge(parent::getDefaultTranslationKeys(), [ 'pattern' ]);
    }

    protected function getHtmlLinkWidgetImplementor()
    {
        $default = '';
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
            $default = 'jsb_Honeybee_Core/ui/HtmlLinkPopup';
        }
        return $this->getOption('htmllink_widget', $default);
    }

    protected function getWidgetImplementor()
    {
        $default = '';
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
            $default = 'jsb_Honeybee_Core/ui/HtmlLinkList';
        }
        return $this->getOption('widget', $default);
    }
}
