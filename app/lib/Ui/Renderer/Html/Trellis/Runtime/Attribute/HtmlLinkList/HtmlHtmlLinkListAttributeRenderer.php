<?php

namespace Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlLinkList;

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\HtmlLink\HtmlLink;
use Trellis\Runtime\Attribute\HtmlLink\HtmlLinkListAttribute;

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

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/HtmlLinkPopup');
    }
}
