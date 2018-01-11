<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Trellis\Runtime\Attribute\Choice\ChoiceAttribute;

class HtmlChoiceListFilterRenderer extends HtmlListFilterRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/choice_attribute.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['allowed_values'] = $this->getAllowedValues();
        $params += $this->getEmptyOptionSettings();

        return $params;
    }

    protected function determineFilterValue()
    {
        $value = parent::determineFilterValue();
        $allowed_values = $this->getAllowedValues();

        if (!in_array($value, $allowed_values)) {
            return array_shift($allowed_values);
        }
        return $value;
    }

    /**
     * @return array
     */
    protected function getAllowedValues()
    {
        $attribute = $this->list_filter->getAttribute();

        if ($attribute instanceof ChoiceAttribute) {
            $allowed_values = (array)$attribute->getOption(ChoiceAttribute::OPTION_ALLOWED_VALUES, []);
        } else {
            $allowed_values = $this->getOption('allowed_values', []);
            // retrieve the array of allowed values from the provided setting name
            if (is_string($allowed_values)) {
                $allowed_values = $this->environment->getSettings()->get($allowed_values, []);
            }
            $allowed_values = (array)$allowed_values;
        }

        // add eventual empty option to allowed values
        $empty_option_settings = $this->getEmptyOptionSettings();
        if ($empty_option_settings['add_empty_option']) {
            array_unshift($allowed_values, $empty_option_settings['empty_option_value']);
        }

        return $allowed_values;
    }

    protected function getEmptyOptionSettings()
    {
        $settings = [];
        $settings['add_empty_option'] = $this->getOption('add_empty_option', false);
        if (!$settings['add_empty_option']) {
            return $settings;
        }
        $settings['empty_option_value'] = $this->getOption('empty_option_value', static::EMPTY_FILTER_VALUE);
        $settings['empty_option_name'] = $this->getOption(
            'empty_option_name',
            $this->lookupTranslation($settings['empty_option_value'], null, '&nbsp;')
        );

        return $settings;
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/list-filter/ChoiceListFilter');
    }
}
