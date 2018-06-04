<?php

namespace Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\Choice;

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;

class HtmlChoiceAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        if ($this->hasInputViewScope()) {
            return $this->output_format->getName() . '/attribute/choice/as_input.twig';
        }
        return $this->output_format->getName() . '/attribute/text-list/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['allowed_values'] = (array)$this->attribute->getOption('allowed_values', []);
        $params['add_empty_option'] = $this->getOption('add_empty_option', false);
        $params['empty_option_name'] = $this->getOption('empty_option_name', '');
        $params['empty_option_value'] = $this->getOption('empty_option_value', $this->attribute->getNullValue());

        return $params;
    }

    protected function getInputTemplateParameters()
    {
        $global_input_parameters = parent::getInputTemplateParameters();

        if (!empty($global_input_parameters['readonly'])) {
            $global_input_parameters['disabled'] = 'disabled';
        }

        return $global_input_parameters;
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/SelectBox');
    }

    protected function getWidgetOptions()
    {
        $allow_empty_option = $this->getOption('allow_empty_option', false) || $this->getOption('add_empty_option', false);

        $widget_options = [
            'allow_empty_option' => $allow_empty_option
        ];

        return array_replace_recursive(parent::getWidgetOptions(), $widget_options);
    }
}
