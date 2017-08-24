<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

class HtmlChoiceAttributeListFilterRenderer extends HtmlListFilterRenderer
{
    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['allowed_values'] = (array)$this->attribute->getOption('allowed_values', []);
        $params['add_empty_option'] = $this->getOption('add_empty_option', false);
        $params['empty_option_name'] = $this->getOption(
            'empty_option_name',
            $this->_($this->list_filter->getName() . '.empty_option_name', null, null, null, '&nbsp;')
        );
        $params['empty_option_value'] = $this->getOption('empty_option_value', $this->attribute->getNullValue());

        return $params;
    }
}
