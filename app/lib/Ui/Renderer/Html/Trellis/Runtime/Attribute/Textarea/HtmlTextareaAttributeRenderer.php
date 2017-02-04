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
        $params['rows'] = $this->getOption('rows', 9);

        // editor like the HtmlRichTextEditor
        $params['editor'] = $this->getEditorParameters();

        $params['widget_options']['textarea_selector'] = '#' . $params['field_id'];

        return $params;
    }

    protected function getEditorParameters()
    {
        $editor = [];

        $editor['enabled'] = $this->isWidgetEnabled() && $this->getOption('editor_enabled', false);
        $editor['autogrow'] = $this->getOption('editor_autogrow', false);
        $editor['twig'] = $this->getOption('editor_twig', 'html/attribute/textarea/htmlrichtexteditor.twig');

        return $editor;
    }

    protected function getWidgetOptions()
    {
        return array_replace_recursive(
            [
                'editor_input_selector' => '.editor-hrte',
                'view_scope' => $this->getOption('view_scope', 'missing_view_scope.collection'),
            ],
            parent::getWidgetOptions()
        );
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/HtmlRichTextEditor');
    }
}
