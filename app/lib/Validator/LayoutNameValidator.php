<?php

namespace Honeygavi\Validator;

use AgaviValidator;

class LayoutNameValidator extends AgaviValidator
{
    protected function validate()
    {
        $list = $this->getParameter('value_map');
        if (!is_array($list)) {
            $this->throwError('value_map');
        }
        $value = $this->getData($this->getArgument());

        if (!is_scalar($value)) {
            $this->throwError('type');
            return false;
        }

        if (!array_key_exists($value, $list)) {
            $this->throwError();
            return false;
        }

        $this->export($list[$value], $this->getParameter('export', $this->getArgument()));

        return true;
    }
}
