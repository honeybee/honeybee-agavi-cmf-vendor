<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\GeoPoint;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\GeoPoint\GeoPointAttribute;

class HtmlGeoPointAttributeRenderer extends HtmlAttributeRenderer
{
    const DEFAULT_VALUE_STEP = 'any';

    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
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
}
