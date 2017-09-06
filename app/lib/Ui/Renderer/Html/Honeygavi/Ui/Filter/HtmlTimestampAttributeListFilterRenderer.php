<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\ValueObjects\HtmlTimestampRangeRenderer;
use Trellis\Runtime\Attribute\Date\DateAttribute;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;

class HtmlTimestampAttributeListFilterRenderer extends HtmlListFilterRenderer
{
    /**
     * List of intervals available for quick selection from the dropdown
     * Must be compatible with comparators defined in Honeygavi\Ui\ListConfig
     */
    protected $default_choice_options = [
        // value => label
        'range(lte:now)' => 'in_the_past',
        'range(gte:now,lte:+1 hour)' => 'next_hour',
        'range(gte:now,lte:+1 day)' => 'next_day',
        'range(gte:now,lte:+1 week)' => 'next_week',
        'range(gte:now,lte:+1 month)' => 'next_month'
    ];

    protected function getDefaultTemplateIdentifier()
    {
        if ((bool)$this->getOption('as_range', false)) {
            return $this->output_format->getName() . '/list_filter/range_timestamp_attribute.twig';
        } else {
            return $this->output_format->getName() . '/list_filter/timestamp_attribute.twig';
        }
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['choice_options'] = (array)$this->getOption('choice_options', $this->default_choice_options);
        $params['rendered_date_range'] = $this->renderDateRange($params['filter_value']);

        return $params;
    }

    protected function renderDateRange($range_value)
    {
        $render_settings = [
            'control_name' => 'filter[' . $this->list_filter->getName() . ']',
            'current_value' => $range_value,
            'translation_key_prefix' => $this->list_filter->getId() . '.',
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
        if ((bool)$this->getOption('as_range', false)) {
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
        $translations = parent::getTranslations($domain);

        $translations['picker_custom'] = $this->_($this->list_filter->getId() . '.picker_custom');
        $translations['quick_label_comparator_gte'] = $this->_($this->list_filter->getId() . '.picker_gte');
        $translations['quick_label_comparator_lte'] = $this->_($this->list_filter->getId() . '.picker_lte');
        $translations['quick_label_with_value'] = $this->getTranslatedQuickLabel();

        return $translations;
    }

    // @todo Add a getTranslatedValue()
    protected function getTranslatedQuickLabel()
    {
        $default_choice_options = HtmlTimestampRangeRenderer::$default_choice_options;
        $filter_id = $this->list_filter->getName();
        $range_translation = '{COMPARATOR} {COMPARAND}, ';
        $value = '';
        foreach (HtmlTimestampRangeRenderer::getRangeLimits($this->list_filter->getCurrentValue()) as $range) {
            $comparator_translation = $this->_($filter_id . '.picker_' . $range['comparator'], null, null, null, $range['comparator']);
            if ($date = \DateTimeImmutable::createFromFormat(DateAttribute::FORMAT_ISO8601, $range['comparand'])) {
                $comparand_translation = $date->format($this->getOption('quick_label_date_format', 'j M Y'));
            } elseif (array_key_exists($range['comparand'], $default_choice_options)) {
                $translation_key = $filter_id . '.picker_' . $default_choice_options[$range['comparand']];
                $comparand_translation = $this->_($translation_key, null, null, null, $range['comparand']);
            } else {
                $comparand_translation = $range['comparand'];
            }
            $range_value = str_replace('{COMPARATOR}', $comparator_translation, $range_translation);
            $range_value = str_replace('{COMPARAND}', $comparand_translation, $range_value);
            $value .= $range_value;
        }
        $value = rtrim(trim($value), ',');
        $params = [
            'id' => $filter_id,
            'name' => $this->list_filter->getName(),
            'value' => $value
        ];
        $quick_label_translation = $this->lookupTranslation('quick_label', [ 'value' => '{VALUE}' ] + $params, "$filter_id: {VALUE}");

        return str_replace('{VALUE}', $value, $quick_label_translation);
    }
}
