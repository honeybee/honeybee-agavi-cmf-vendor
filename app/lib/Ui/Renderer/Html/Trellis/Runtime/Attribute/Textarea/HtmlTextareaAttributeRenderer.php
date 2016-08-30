<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\Textarea;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\Textarea\TextareaAttribute;

class HtmlTextareaAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        $input_suffixes = $this->getInputViewTemplateNameSuffixes($this->output_format->getName());

        if (StringToolkit::endsWith($view_scope, $input_suffixes)) {
            return $this->output_format->getName() . '/attribute/textarea/as_input.twig';
        }

        return $this->output_format->getName() . '/attribute/textarea/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['maxlength'] = $this->getOption(
            'maxlength',
            $this->attribute->getOption(TextareaAttribute::OPTION_MAX_LENGTH)
        );

        if (is_numeric($params['maxlength'])) {
            $params['attribute_value'] = substr($params['attribute_value'], 0, $params['maxlength']);
        }
        $params['wrap'] = $this->getOption('wrap', '');
        $params['cols'] = $this->getOption('cols', '');
        $params['rows'] = $this->getOption('rows', 12);
        $params['syntax'] = $this->getSyntaxParameters();

        return $params;
    }

    protected function getSyntaxParameters()
    {
        $syntax_params = (array)$this->getOption('syntax', []);

        if (isset($syntax_params['enabled'])) {
            $syntax_params['name'] = isset($syntax_params['name']) ? $syntax_params['name'] : '';
            $syntax_params['preview'] = isset($syntax_params['preview']) ? $syntax_params['preview'] : '';
        }

        return $syntax_params;
    }
}
