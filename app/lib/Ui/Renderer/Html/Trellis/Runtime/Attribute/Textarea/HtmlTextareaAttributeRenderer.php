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

        // editor like the HtmlRichTextEditor
        $params['editor'] = $this->getEditorParameters($params);

        return $params;
    }

    protected function getEditorParameters(array $current_params)
    {
        $editor = [];

        $editor['enabled'] = $this->getOption('editor_enabled', false);
        $editor['twig'] = $this->getOption('editor_twig', 'html/attribute/textarea/htmlrichtexteditor.twig');
        $editor['options'] = json_encode(
            array_merge(
                [
                    'textarea_selector' => '#' . $current_params['field_id'],
                    'editor_input_selector' => '.editor-hrte',
                    'view_scope' => $this->getOption('view_scope', 'missing_view_scope.collection'),
                ],
                (array)$this->getOption('editor_options', [])
            )
        );

        return $editor;
    }
}
