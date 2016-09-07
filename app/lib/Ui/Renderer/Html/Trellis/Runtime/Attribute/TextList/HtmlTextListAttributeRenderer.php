<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\TextList;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\TextList\TextListAttribute;

class HtmlTextListAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
            return $this->output_format->getName() . '/attribute/text-list/as_input.twig';
        }

        return $this->output_format->getName() . '/attribute/text-list/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['grouped_field_name'] = $params['grouped_field_name'] . '[]';
        $value = $params['attribute_value'];

        $missing_allowed_values = [];
        foreach ($this->getAllowedValues() as $allowed_value) {
            if (!in_array($allowed_value, $value)) {
                $missing_allowed_values[] = $allowed_value;
            }
        }
        $params['unchecked_options'] = $missing_allowed_values;

        return $params;
    }

    protected function determineAttributeValue($attribute_name)
    {
        $value = [];

        if ($this->hasOption('value')) {
            $value = $this->getOption('value');
            $value = is_array($value) ? $value : [ $value ];
            return $value;
        }

        $expression = $this->getOption('expression');
        if (!empty($expression)) {
            $value = $this->evaluateExpression($expression);
        } else {
            $value = $this->getPayload('resource')->getValue($attribute_name);
        }

        $value = is_array($value) ? $value : [ $value ];

        return $value;
    }

    protected function getWidgetOptions()
    {
        $widget_options = parent::getWidgetOptions();

        $widget_options['min_count'] = $this->getMinCount($this->isRequired());
        $widget_options['max_count'] = $this->getMaxCount();
        $widget_options['allowed_values'] = $this->getAllowedValues();
        if ($this->hasOption('remove_label')) {
            $widget_options['remove_label'] = $this->_(
                $this->getOption('remove_label'),
                $this->getTranslationDomain()
            );
        }
        $widget_options['remove_title'] = $this->_(
            $this->getOption('remove_title', 'text_list_remove_title'),
            $this->getTranslationDomain()
        );

        return $widget_options;
    }

    protected function getMinCount($is_required = false)
    {
        $min_count = $this->getOption(
            'min_count',
            $this->attribute->getOption(TextListAttribute::OPTION_MIN_COUNT)
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
            $this->attribute->getOption(TextListAttribute::OPTION_MAX_COUNT)
        );
    }

    protected function getAllowedValues()
    {
        return $this->attribute->getOption(TextListAttribute::OPTION_ALLOWED_VALUES, []);
    }

    protected function isRequired()
    {
        $is_required = parent::isRequired();

        $text_list = $this->determineAttributeValue($this->attribute->getName());

        // check options against actual value
        $items_number = count($text_list);
        $min_count = $this->getMinCount($is_required);

        if (is_numeric($min_count) && $items_number < $min_count) {
            $is_required = true;
        }

        return $is_required;
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/TextList');
    }
}
