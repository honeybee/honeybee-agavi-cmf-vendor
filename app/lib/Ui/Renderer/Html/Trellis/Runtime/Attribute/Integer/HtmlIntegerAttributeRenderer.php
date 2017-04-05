<?php

namespace Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\Integer;

use Honeybee\Common\Util\StringToolkit;
use Trellis\Runtime\Attribute\Integer\IntegerAttribute;
use Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;

class HtmlIntegerAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
            return $this->output_format->getName() . '/attribute/integer/as_input.twig';
        }

        return $this->output_format->getName() . '/attribute/integer/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        // load settings or fallback to default attribute's options
        $params['min_value'] = $this->getOption(
            'min_value',
            $this->attribute->getOption(IntegerAttribute::OPTION_MIN_VALUE)
        );
        $params['max_value'] = $this->getOption(
            'max_value',
            $this->attribute->getOption(IntegerAttribute::OPTION_MAX_VALUE)
        );
        $params['value_step'] = $this->getOption('value_step');

        // verify the parameters are valid with integers
        $php_int_min = ~PHP_INT_MAX;

        foreach ([ 'min_value', 'max_value', 'value_step'] as $key) {
            if (is_numeric($params[$key]) && $params[$key] <= PHP_INT_MAX && $params[$key] >= $php_int_min) {
                // truncate to integer eventual float values
                $params[$key] = intval(floor($params[$key]));
            } else {
                $params[$key] = '';
            }
        }

        $params['maxlength'] = $this->getOption('maxlength', '');

        if (!$this->hasOption('placeholder')) {
            $params['placeholder'] = sprintf(
                '%s…%s',
                $params['min_value'],
                $params['max_value']
            );
        }

        return $params;
    }

    protected function getDefaultTranslationKeys()
    {
        return array_merge(parent::getDefaultTranslationKeys(), [ 'pattern' ]);
    }
}
