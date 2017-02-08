<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\Float;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\Float\FloatAttribute;

class HtmlFloatAttributeRenderer extends HtmlAttributeRenderer
{
    const DEFAULT_VALUE_STEP = 0.001;

    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
            return $this->output_format->getName() . '/attribute/float/as_input.twig';
        }

        return $this->output_format->getName() . '/attribute/float/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        // load settings or fallback to default attribute's options
        $params['min_value'] = $this->getOption(
            'min_value',
            $this->attribute->getOption(FloatAttribute::OPTION_MIN_VALUE)
        );
        $params['max_value'] = $this->getOption(
            'max_value',
            $this->attribute->getOption(FloatAttribute::OPTION_MAX_VALUE)
        );
        $params['value_step'] = $this->getOption('value_step', self::DEFAULT_VALUE_STEP);

        // verify the parameters are valid with floats
        foreach ([ 'min_value', 'max_value', 'value_step' ] as $key) {
            if ($key === 'value_step' && $params[$key] === 'any') {
                continue;
            }
            if (is_numeric($params[$key])) {
                $params[$key] = floatval($params[$key]);
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
