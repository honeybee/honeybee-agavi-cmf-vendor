<?php

namespace Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlLink;

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\HtmlLink\HtmlLink;

class HtmlHtmlLinkAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        if ($this->hasInputViewScope()) {
            return $this->output_format->getName() . '/attribute/html-link/as_input.twig';
        }
        return $this->output_format->getName() . '/attribute/html-link/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

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
            return new HtmlLink([]);
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
        if ($this->hasInputViewScope()) {
            $default = 'jsb_Honeybee_Core/ui/HtmlLinkPopup';
        }
        return $this->getOption('htmllink_widget', $default);
    }
}
