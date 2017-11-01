<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\ValueObjects\HtmlTimestampRangeRenderer;
use Trellis\Runtime\Attribute\Date\DateAttribute;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;

class HtmlTimestampListFilterRenderer extends HtmlListFilterRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        if ((bool)$this->getOption('as_range', true)) {
            return $this->output_format->getName() . '/list_filter/range_timestamp_attribute.twig';
        } else {
            return $this->output_format->getName() . '/list_filter/timestamp_attribute.twig';
        }
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['choice_options'] = $this->getDefaultChoiceOptions();
        $params['rendered_date_range'] = $this->renderDateRange($params['filter_value']);

        return $params;
    }

    protected function getDefaultChoiceOptions()
    {
        return (array)$this->getOption('choice_options', HtmlTimestampRangeRenderer::$default_choice_options);
    }

    protected function renderDateRange($range_value)
    {
        $render_settings = [
            'control_name' => 'filter[' . $this->list_filter->getName() . ']',
            'current_value' => $range_value,
            'choice_options' => $this->getDefaultChoiceOptions(),
            'translation_key_prefix' => $this->getFilterConfigKey() . '.',
            'widget_enabled' => false, // list-filter widget takes care of it
            'css_prefix' => 'hb-list-filter-',
            'translation_domain' => $this->getTranslationDomain()
        ];

        return $this->renderer_service->renderSubject(
            $range_value,
            $this->output_format,
            new ArrayConfig([ 'renderer' => $this->getOption('range_renderer', HtmlTimestampRangeRenderer::CLASS) ]),
            [],
            new Settings($render_settings)
        );
    }

    protected function getWidgetImplementor()
    {
        if ((bool)$this->getOption('as_range', true)) {
            return $this->getOption('widget', 'jsb_Honeybee_Core/ui/list-filter/DateRangePickerListFilter');
        } else {
            return $this->getOption('widget', 'jsb_Honeybee_Core/ui/list-filter/DatePickerListFilter');
        }
    }

    protected function getWidgetOptions()
    {
        $widget_options = parent::getWidgetOptions();

        if ($this->hasOption('default_range_value')) {
            $widget_options['default_range_value'] = $this->getOption('default_range_value');
        }

        return $widget_options;
    }

    protected function getTranslations($domain = null)
    {
        $i18n = parent::getTranslations($domain);

        $i18n['picker_custom'] = $this->lookupTranslation('picker_custom');
        $i18n['picker_placeholder'] = $this->lookupTranslation('picker_placeholder', null, '');
        $i18n['quick_label_comparator_gte'] = $this->lookupTranslation('picker_gte');
        $i18n['quick_label_comparator_lte'] = $this->lookupTranslation('picker_lte');
        $i18n['quick_label_with_value'] = $this->getTranslatedQuickLabel();

        return $i18n;
    }

    protected function getTranslatedQuickLabel()
    {
        $value = '';
        $filter_name = $this->list_filter->getName();
        $config_key = $this->getFilterConfigKey();
        $range_translation = '{COMPARATOR} {COMPARAND}, ';
        $current_value = $this->determineFilterValue();
        $default_choice_options = $this->getDefaultChoiceOptions();
        $default_option = $default_choice_options[$current_value] ?? null;
        $has_value_translation = $default_option
            && $translated_value = $this->_($config_key . '.picker_' . $default_option);

        if ($has_value_translation) {
            $value = $translated_value;
        } else {
            // formatted translations for range-limits
            foreach (HtmlTimestampRangeRenderer::getRangeLimits($current_value) as $range) {
                $comparator_translation = $this->_($config_key . '.picker_' . $range['comparator'], null, null, null, $range['comparator']);
                if ($date = \DateTimeImmutable::createFromFormat(DateAttribute::FORMAT_ISO8601, $range['comparand'])) {
                    $comparand_translation = $date->format($this->getOption('quick_label_date_format', 'j M Y'));
                } elseif (array_key_exists($range['comparand'], $default_choice_options)) {
                    $translation_key = $config_key . '.picker_' . $default_choice_options[$range['comparand']];
                    $comparand_translation = $this->_($translation_key, null, null, null, $range['comparand']);
                } else {
                    $comparand_translation = $range['comparand'];
                }
                $range_value = str_replace('{COMPARATOR}', $comparator_translation, $range_translation);
                $range_value = str_replace('{COMPARAND}', $comparand_translation, $range_value);
                $value .= $range_value;
            }
            $value = rtrim(trim($value), ',');
        }
        $params = [
            'config_key' => $config_key,
            'name' => $this->list_filter->getName(),
            'value' => $value
        ];
        $quick_label_translation = $this->lookupTranslation('quick_label', [ 'value' => '{VALUE}' ] + $params, "$filter_name: {VALUE}");

        return str_replace('{VALUE}', $value, $quick_label_translation);
    }
}
