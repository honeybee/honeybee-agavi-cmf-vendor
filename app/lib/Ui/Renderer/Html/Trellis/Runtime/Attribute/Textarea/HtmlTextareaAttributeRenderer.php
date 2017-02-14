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
        $params['editor']['options']['textarea_selector'] = '#' . $params['field_id'];

        $params['counter'] = $this->getCounterParameters();

        // counter just on the editor
        if ($params['editor']['enabled'] && $params['counter']['enabled']) {
            $params['editor']['options']['counter_enabled'] = true;
            $params['editor']['options']['counter_config'] = $params['counter']['options'];
            $params['counter']['enabled'] = false;
        }

        return $params;
    }

    protected function getEditorParameters()
    {
        $editor = [];

        $editor['enabled'] = $this->getOption('widget_enabled', true)
            ? (bool)$this->getOption('editor_enabled', false)
            : false;
        $editor['autogrow'] = $this->getOption('editor_autogrow', false);
        $editor['twig'] = $this->getOption('editor_twig', 'html/attribute/textarea/htmlrichtexteditor.twig');
        $editor['options'] = array_replace_recursive(
            [
                'editor_input_selector' => '.editor-hrte',
                'view_scope' => $this->getOption('view_scope', 'missing_view_scope.collection'),
            ],
            (array)$this->getOption('editor_options', [])
        );

        return $editor;
    }

    protected function getCounterParameters()
    {
        $counter = [];

        $counter['enabled'] = $this->getOption('widget_enabled', true)
            ? (bool)$this->getOption('counter_enabled', false)
            : false;
        $counter['options'] = array_replace_recursive(
            [
                'view_scope' => $this->getOption('view_scope', 'missing_view_scope.collection')
            ],
            (array)$this->getOption('counter_options', [])
        );

        return $counter;
    }
}
