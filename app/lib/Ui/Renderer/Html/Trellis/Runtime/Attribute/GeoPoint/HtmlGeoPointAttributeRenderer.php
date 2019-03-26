<?php

namespace Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\GeoPoint;

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\GeoPoint\GeoPointAttribute;

class HtmlGeoPointAttributeRenderer extends HtmlAttributeRenderer
{
    const DEFAULT_VALUE_STEP = 'any';

    protected function getDefaultTemplateIdentifier()
    {
        if ($this->hasInputViewScope()) {
            return $this->output_format->getName() . '/attribute/geo-point/as_input.twig';
        }
        return $this->output_format->getName() . '/attribute/geo-point/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['value_step'] = $this->getOption('value_step', self::DEFAULT_VALUE_STEP);

        // verify the parameters are valid with floats
        foreach ([ 'value_step' ] as $key) {
            if ($key === 'value_step' && $params[$key] === 'any') {
                continue;
            }
            if (is_numeric($params[$key])) {
                $params[$key] = floatval($params[$key]);
            } else {
                $params[$key] = '';
            }
        }

        return $params;
    }

    protected function getDefaultTranslationKeys()
    {
        $default_translation_keys = parent::getDefaultTranslationKeys();

        $field_translation_keys = [
            'title_lon',
            'title_lat',
            'placeholder_lon',
            'placeholder_lat'
        ];

        return array_unique(array_merge($default_translation_keys, $field_translation_keys));
    }

    protected function getWidgetOptions()
    {
        $widget_options = parent::getWidgetOptions();
        if (isset($widget_options['geo_endpoint'])) {
            $widget_options['geo_endpoint'] = $this->genUrl($widget_options['geo_endpoint']);
        }
        return $widget_options;
    }
}
