<?php

namespace Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\Text;

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\Text\TextAttribute;

class HtmlTextAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        if ($this->hasInputViewScope()) {
            return $this->output_format->getName() . '/attribute/text/as_input.twig';
        }
        return $this->output_format->getName() . '/attribute/text/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['maxlength'] = $this->getOption(
            'maxlength',
            $this->attribute->getOption(TextAttribute::OPTION_MAX_LENGTH)
        );

        return $params;
    }

    protected function getDefaultTranslationKeys()
    {
        return array_merge(parent::getDefaultTranslationKeys(), [ 'pattern' ]);
    }
}
