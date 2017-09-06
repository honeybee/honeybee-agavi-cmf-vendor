<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Trellis\Runtime\Attribute\Date\DateAttribute;

class HtmlBooleanAttributeListFilterRenderer extends HtmlListFilterRenderer
{
    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/list-filter/BooleanListFilter');
    }

    protected function getTranslations($domain = null)
    {
        $translations = parent::getTranslations($domain);

        $filter_name = $this->list_filter->getName();
        $filter_value = (int)$this->list_filter->getCurrentValue();

        $translations['value_0'] = $this->_($filter_name . '.value_0', null, null, null, '0');
        $translations['value_1'] = $this->_($filter_name . '.value_1', null, null, null, '1');
        $translations['quick_label_with_value'] = str_replace(
            '{VALUE}',
            $translations['value_' . $filter_value],
            $this->_($filter_name . '.quick_label')
        );

        return $translations;
    }
}
