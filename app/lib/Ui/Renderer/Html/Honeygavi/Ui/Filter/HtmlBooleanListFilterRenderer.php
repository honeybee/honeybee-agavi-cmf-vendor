<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

class HtmlBooleanListFilterRenderer extends HtmlListFilterRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/boolean_attribute.twig';
    }

    /**
     * @return array
     */
    protected function getAllowedValues()
    {
        return [ '0', '1' ];
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/list-filter/BooleanListFilter');
    }
}
