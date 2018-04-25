<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeygavi\Ui\Filter\ListFilter;
use Honeygavi\Ui\Filter\ListFilterInterface;
use Honeygavi\Ui\Filter\ListFilterValue;
use Honeygavi\Ui\Renderer\Renderer;
use Trellis\Runtime\Attribute\AttributeInterface;

class HtmlListFilterRenderer extends Renderer
{
    const STATIC_TRANSLATION_PATH = 'list_filters';
    const EMPTY_FILTER_VALUE = ListFilterValue::EMPTY_FILTER_VALUE;
    const DELIMITER_AND = ListFilterValue::DELIMITER_AND;

    protected $list_filter;
    protected $attribute;

    protected function setUp($payload, $settings)
    {
        parent::setUp($payload, $settings);
        $this->list_filter = $payload['subject'];
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->list_filter = null;
        $this->attribute = null;
    }

    protected function validate()
    {
        if (!$this->list_filter instanceof ListFilterInterface) {
            throw new RuntimeError('Payload "subject" must be an instance of: ' . ListFilterInterface::CLASS);
        }
        $attribute = $this->list_filter->getAttribute();
        if ($attribute && !$attribute instanceof AttributeInterface) {
            throw new RuntimeError('Filter attribute must be an instance of: ' . AttributeInterface::CLASS);
        }
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/pick_template.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $attribute = $this->list_filter->getAttribute();

        // params for pick-template
        $params['resource_type_prefix'] = $this->list_filter->getSettings()->get(
            'resource_type_prefix',
            $this->getPayload('resource')->getType()->getPrefix()
        );
        $params['attribute_name'] = 'missing';
        $params['attribute_type_name'] = 'missing';
        if ($attribute) {
            $params['attribute_name'] = $attribute->getName();
            $params['attribute_type_name'] = $this->name_resolver->resolve($attribute);
        }
        $params['filter_value'] = $this->determineFilterValue();
        $params['filter_name'] = $this->list_filter->getName();
        $params['empty_filter_value'] = $this->getOption('empty_filter_value', static::EMPTY_FILTER_VALUE);
        $params['config_key'] = $this->getFilterConfigKey();
        $params['html_attributes'] = $this->getOption('html_attributes', []);
        // render inner form, when form-parameters are passed (e.g to support no-js submit of filter)
        $params['form_parameters'] = $this->getOption('form_parameters', []);
        $params['form_url'] = $this->url_generator->generateUrl(null);
        $params['widget_enabled'] = $this->isWidgetEnabled();
        $params['widget_options'] = $this->getWidgetOptions();
        $params['widget_options']['translations'] = $params['translations'];
        $params['widget_classes'] = $this->isWidgetEnabled() ? sprintf(' jsb_ %s', $this->getWidgetImplementor()) : '';
        if ($this->hasOption('tabindex')) {
            $params['tabindex'] = $this->getOption('tabindex');
        }
        $params['css_prefix'] = $this->getOption('css_prefix', 'hb-list-filter');
        $css = (string)$this->getOption('css', '');
        if ($attribute) {
            $css .= sprintf(' %s_%s', $params['css_prefix'], $params['attribute_name']);
        }
        $params['css'] = $css;

        return $params;
    }

    protected function determineFilterOperator()
    {
        return $this->list_filter->getCurrentValue()->getOperator();
    }

    protected function determineFilterValue()
    {
        // get value according to options
        $current_value = $this->list_filter->getCurrentValue();
        $default_value = $this->getOption('default_values', [])[0] ?? null;
        $value = $current_value->isEmpty() ? $default_value : $current_value->last();

        return (string)$value;
    }

    protected function getFilterConfigKey()
    {
        return $this->list_filter->getSettings()->get('config_key');
    }

    protected function getTranslations($domain = null)
    {
        $i18n = [];
        $config_key = $this->getFilterConfigKey();
        $filter_name = $this->list_filter->getName();
        $filter_value = $this->determineFilterValue();

        $params = [
            'config_key' => $config_key,
            'name' => $filter_name,
            'value' => $filter_value
        ];

        // values translations
        $i18n += $this->getValuesTranslations();
        $i18n['filter_value_translation'] = $i18n['filter_value_translation']
            ?? $i18n['value_' . $filter_value]
            ?? $filter_value;

        // quick control
        $i18n['quick_label'] = $this->lookupTranslation(
            'quick_label',
            [ 'value' => '{VALUE}' ] + $params,  // lookup without value
            "$filter_name: {VALUE}"
        );
        $i18n['quick_label_with_current_value'] = str_replace(
            '{VALUE}',
            $i18n['filter_value_translation'],
            $i18n['quick_label']
        );
        $i18n['quick_label_title'] = $this->lookupTranslation('quick_label_title', $params);
        $i18n['quick_clear'] = $this->lookupTranslation('quick_clear', $params, 'x');
        $i18n['quick_clear_title'] = $this->lookupTranslation('quick_clear_title', $params);
        // filter detail
        $i18n['filter_label'] = $this->lookupTranslation(
            'filter_label',
            [ 'value' => '{VALUE}' ] + $params,  // lookup without value
            $i18n['quick_label']
        );
        $i18n['filter_label_with_current_value'] = str_replace(
            '{VALUE}',
            $i18n['filter_value_translation'],
            $i18n['filter_label']
        );
        $i18n['filter_placeholder'] = $this->lookupTranslation('filter_placeholder', $params, '');
        $i18n['input_help'] = $this->lookupTranslation('input_help', $params, '');

        return $i18n;
    }

    protected function lookupTranslation($text, array $params = null, $fallback = null, $domain = null, $locale = null)
    {
        $config_key = $params['config_key'] ?? $this->getFilterConfigKey();
        $value = $params['value'] ?? '{NOVALUE}';

        return $this->_(
            $config_key . ".$text.value_" . $value,
            $domain,
            $locale,
            $params,
            $this->_(
                $config_key . ".$text",
                $domain,
                $locale,
                $params,
                $this->_($text, $domain, $locale, $params, $fallback)
            )
        );
    }

    protected function getValuesTranslations(array $default_values = [])
    {
        $config_key = $this->getFilterConfigKey();
        $translations = [];

        // translate also current string value
        $filter_value = $this->determineFilterValue();
        if (!empty($filter_value) && is_string($filter_value)) {
            $default_values[] = $filter_value;
        }
        $values = array_replace($default_values, $this->getAllowedValues());

        foreach ($values as $value) {
            $translations['value_' . $value] = $this->_($config_key . '.value_' . $value, null, null, null, $value);
        }

        return $translations;
    }

    /**
     * @return array
     */
    protected function getAllowedValues()
    {
        $allowed_values = $this->getOption('allowed_values', []);
        // retrieve the array of allowed values from the provided setting name
        if (is_string($allowed_values)) {
            $allowed_values = $this->environment->getSettings()->get($allowed_values, []);
        }

        return (array)$allowed_values;
    }

    protected function isWidgetEnabled()
    {
        return (bool)$this->getOption('widget_enabled', $this->getWidgetImplementor() !== null);
    }

    protected function getWidgetOptions()
    {
        $widget_options = [
            'prefix' => sprintf(
                '%s[%s]',
                str_replace('jsb_', '', $this->getWidgetImplementor()),
                $this->list_filter->getName()
            )
        ];
        if ($this->hasOption('tabindex')) {
            $widget_options['tabindex'] = $this->getOption('tabindex');
        }

        return array_replace_recursive($widget_options, (array)$this->getOption('widget_options', []));
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/ListFilter');
    }

    /**
     * Default translation domain for activity maps follows this fallback sequence:
     *  - 'view_scope' option
     *  - application translation domain
     *
     * To override with a custom value pass to the renderer the 'translation_domain' option
     */
    protected function getDefaultTranslationDomain()
    {
        $view_scope = $this->getOption('view_scope');

        if (empty($view_scope)) {
            $translation_domain_prefix = parent::getDefaultTranslationDomain();
        } else {
            // convention on view_scope value: the first 3 parts = vendor.package.resource_type
            $view_scope_parts = explode('.', $view_scope);
            $translation_domain_prefix = implode('.', array_slice($view_scope_parts, 0, 3));
        }

        $translation_domain = sprintf(
            '%s.%s',
            $translation_domain_prefix,
            self::STATIC_TRANSLATION_PATH
        );

        return $translation_domain;
    }
}
