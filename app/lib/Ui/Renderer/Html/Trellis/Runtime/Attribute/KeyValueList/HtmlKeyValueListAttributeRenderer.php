<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\KeyValueList;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\KeyValueList\KeyValueListAttribute;

class HtmlKeyValueListAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        if (StringToolkit::endsWith($view_scope, 'collection')) {
            return $this->output_format->getName() . '/attribute/key-value-list/as_itemlist_item_cell.twig';
        }

        return $this->output_format->getName() . '/attribute/key-value-list/as_input.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['allowed_keys'] = $this->attribute->getOption(KeyValueListAttribute::OPTION_ALLOWED_KEYS, $this->getOption('allowed_keys', []));
        $params['allowed_values'] = $this->attribute->getOption(KeyValueListAttribute::OPTION_ALLOWED_VALUES, $this->getOption('allowed_values', []));

        if ($this->attribute->hasOption(KeyValueListAttribute::OPTION_ALLOWED_PAIRS) || $this->hasOption('allowed_pairs')) {
            $params['allowed_pairs'] = $this->getOption(
                'allowed_pairs',
                $this->attribute->getOption(KeyValueListAttribute::OPTION_ALLOWED_PAIRS, [])
            );
            if ($params['allowed_pairs'] instanceof SettingsInterface) {
                $params['allowed_pairs'] = $params['allowed_pairs']->toArray();
            }
            $params['allowed_keys'] = array_keys($params['allowed_pairs']);
            $params['allowed_values'] = array_values($params['allowed_pairs']);
        }


        $params['hide_pair_labels'] = $this->getOption('hide_pair_labels', false);
        $params['key_maxlength'] = $this->getOption('key_maxlength');
        $params['value_maxlength'] = $this->getOption(
            'value_maxlength',
            $this->attribute->getOption(KeyValueListAttribute::OPTION_MAX_LENGTH)
        );
        $params['value_type'] = $this->getOption(
            'value_type',
            $this->attribute->getOption(KeyValueListAttribute::OPTION_VALUE_TYPE, 'text')
        );
        switch ($params['value_type']) {
            case 'integer':
            case 'float':
                $params['value_type'] = 'number';
                $params['min'] = $this->getOption('min', $this->attribute->getOption(KeyValueListAttribute::OPTION_MIN_VALUE));
                $params['max'] = $this->getOption('max', $this->attribute->getOption(KeyValueListAttribute::OPTION_MAX_VALUE));
                break;
            case 'boolean':
                // @todo implement
                // break;
            default:
                $params['value_type'] = 'text';
                break;
        }

        $params['attribute_value'] = $this->normalizeAttributeValue(
            $params['attribute_value'],
            [
                'key_maxlength' => $params['key_maxlength'],
                'value_maxlength' => $params['value_maxlength']
            ]
        );

        return $params;
    }

    protected function determineAttributeValue($attribute_name, $default_value = [])
    {
        $value = [];

        if ($this->hasOption('value')) {
            return $this->getOption('value', $default_value);
        }

        $value_path = $this->getOption('attribute_value_path');
        if (!empty($value_path)) {
            $value = AttributeValuePath::getAttributeValueByPath($this->getPayload('resource'), $value_path);
        } else {
            $value = $this->getPayload('resource')->getValue($attribute_name);
        }

        if (!is_array($value)) {
            throw new RuntimeError('Attribute value is not an array of key/value pairs.');
        }

        if ($value === $this->attribute->getNullValue()) {
            return $default_value;
        } else {
            return $value;
        }
    }

    protected function normalizeAttributeValue($attribute_value, $options)
    {
        // @todo Purge invalid keys/values
        //       Purge values not compliant wth other options

        if (isset($options['key_maxlength']) && is_numeric($options['key_maxlength'])) {
            foreach ($attribute_value as $key => $value) {
                unset($attribute_value[$key]);
                $attribute_value[substr($key, 0, $options['key_maxlength'])] = $value;
            }
        }
        if (isset($options['value_maxlength']) && is_numeric($options['value_maxlength'])) {
            foreach ($attribute_value as $key => $value) {
                $attribute_value[$key] = substr($value, 0, $options['value_maxlength']);
            }
        }

        return $attribute_value;
    }
}
