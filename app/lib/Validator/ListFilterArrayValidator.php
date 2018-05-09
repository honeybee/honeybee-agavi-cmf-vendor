<?php

namespace Honeygavi\Validator;

use Honeygavi\Ui\Filter\FilterValueInterface;
use Honeygavi\Ui\Filter\ListFilterValue;

class ListFilterArrayValidator extends ArrayValidator
{
    protected function export($value, $argument = null, $result = null)
    {
        $value_implementor = $this->getParameter('value_implementor', ListFilterValue::class);

        $value = array_map(function ($val) use ($value_implementor) {
            return $val instanceof FilterValueInterface ? $val : new $value_implementor($val);
        }, $value);

        parent::export($value, $argument, $result);
    }
}
