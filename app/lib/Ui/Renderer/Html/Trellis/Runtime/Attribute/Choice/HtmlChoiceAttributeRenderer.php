<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\Choice;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;

class HtmlChoiceAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        if (StringToolkit::endsWith($view_scope, 'collection')) {
            return $this->output_format->getName() . '/attribute/text-list/as_itemlist_item_cell.twig';
        }

        return $this->output_format->getName() . '/attribute/choice/as_input.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['allowed_values'] = $this->attribute->getOption('allowed_values', []);

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
        return 'jsb_Honeybee_Core/ui/SelectBox';
    }
}
