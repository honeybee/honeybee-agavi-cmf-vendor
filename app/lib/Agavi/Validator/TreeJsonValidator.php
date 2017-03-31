<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;

class TreeJsonValidator extends AgaviValidator
{
    protected function validate()
    {
        $dataJson = $this->getData($this->getArgument());

        $tree = json_decode($dataJson, true);

        if ($tree !== null && !json_last_error()) {
            if ($this->hasParameter('export')) {
                $this->export($tree, $this->getParameter('export'));
            } else {
                $this->export($tree, $this->getArgument());
            }

            return true;
        }

        $this->throwError('format');

        return false;
    }
}
