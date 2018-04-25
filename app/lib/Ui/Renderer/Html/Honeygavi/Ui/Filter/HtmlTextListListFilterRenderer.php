<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeygavi\Ui\Filter\ListFilterValue;
use Trellis\Runtime\Attribute\TextList\TextListAttribute;

class HtmlTextListListFilterRenderer extends HtmlListFilterRenderer
{
    const RENDER_MULTIPLE_VALUE = true;

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/text_list_attribute.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['filter_value_as_array'] = $this->determineFilterValues();
        $params['join_delimiter'] = self::DELIMITER_AND;
        $params = $this->getTextListSettings() + $params;
        $params['allowed_values'] = $this->getAllowedValues();
        $params['placeholder'] = $this->lookupTranslation('placeholder');

        return $params;
    }

    protected function determineFilterValues()
    {
        // get value according to options
        $current_value = $this->list_filter->getCurrentValue();
        $values = $current_value->isEmpty() ? (array)$this->getOption('default_values') : $current_value->getValues();
        // remove empty values
        $values = array_filter($values, function ($value) {
            return !empty(trim($value));
        });

        return (array)$values;
    }

    protected function getTranslations($domain = null)
    {
        $i18n = parent::getTranslations($domain);

        $settings = $this->getTextListSettings();

        // unique help-text, or distinct help-text depending on matching operation (AND/OR)
        $input_help_key = $settings['join_values'] ? 'input_help_conjunction' : 'input_help_disjunction';
        $i18n['input_help'] = $this->lookupTranslation(
            $input_help_key,
            null,
            $this->lookupTranslation('input_help', null, '')
        );

        return $i18n;
    }

    protected function getValuesTranslations(array $default_values = [])
    {
        $filter_values = $this->determineFilterValues();
        $translations = parent::getValuesTranslations($filter_values);
        $value_translations = [];

        // support translation of single values, in junctioned translation
        foreach ($filter_values as $value) {
            $value = trim($value);
            $value_translations[] = $translations['value_'.$value] ?? $value;
        }
        if (!empty($value_translations)) {
            $translations['filter_value_translation'] = join(self::DELIMITER_AND . ' ', $value_translations);
        }

        return $translations;
    }

    /**
     * Return configured allowed-values. Include user inputs when nothing is configured.
     * @return array
     */
    protected function getAllowedValues()
    {
        $allowed_values = $this->getConfiguredAllowedValues();

        if ($this->getInputAllowedOption($this->getJoinValuesOption())) {
            $allowed_values = array_unique(
                array_merge($allowed_values, $this->determineFilterValues())
            );
        }

        return $allowed_values;
    }

    protected function getConfiguredAllowedValues()
    {
        $attribute = $this->list_filter->getAttribute();

        if ($attribute instanceof TextListAttribute) {
            $allowed_values = (array)$attribute->getOption(TextListAttribute::OPTION_ALLOWED_VALUES, []);
        } else {
            $allowed_values = $this->getOption('allowed_values', []);
            // retrieve the array of allowed values from the provided setting name
            if (is_string($allowed_values)) {
                $allowed_values = $this->environment->getSettings()->get($allowed_values, []);
            }
            $allowed_values = (array)$allowed_values;
        }

        return $allowed_values;
    }

    protected function getWidgetOptions()
    {
        $default_options = [
            'allow_empty_option' => $this->getOption('allow_empty_option', true)
        ];

        return array_replace(
            $default_options,
            parent::getWidgetOptions(),
            $this->getTextListSettings()
        );
    }

    protected function getTextListSettings()
    {
        // join values changes logical operator (true = AND, false = OR)
        $join_values = $this->getJoinValuesOption();

        return [
            'join_values' => $join_values,
            'input_allowed' => $this->getInputAllowedOption($join_values)
        ];
    }

    /**
     * Input is allowed when no specific allowed-values or no specific option is specified
     */
    protected function getInputAllowedOption($default = false)
    {
        $default = count($this->getConfiguredAllowedValues()) === 0 ? true : $default;

        return $this->getOption('input_allowed', $default);
    }

    protected function getJoinValuesOption($default = false)
    {
        switch ($this->determineFilterOperator()) {
            case ListFilterValue::OP_OR:
                $default = false;
                break;
            case ListFilterValue::OP_AND:
                $default = true;
                break;
            default:
        }
        return $this->getOption('join_values', $default);
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/list-filter/TextListListFilter');
    }
}
