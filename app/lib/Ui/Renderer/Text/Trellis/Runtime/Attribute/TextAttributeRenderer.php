<?php

namespace Honeygavi\Ui\Renderer\Text\Trellis\Runtime\Attribute;

use Honeygavi\Ui\Renderer\AttributeRenderer;

class TextAttributeRenderer extends AttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/attribute/as_list_item.twig';
    }

    protected function getTemplateParameters()
    {
        return parent::getTemplateParameters();
    }
}
