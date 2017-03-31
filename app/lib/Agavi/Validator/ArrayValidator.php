<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;

class ArrayValidator extends AgaviValidator
{
    // @todo add a more detailed (secure & configurable) implementation.
    protected function validate()
    {
        $data = $this->getData($this->getArgument());
        if (is_string($data) && $this->getParameter('explode_strings', false)) {
            $data = explode($this->getParameter('separator', ','), $data);
        }

        if (is_array($data)) {
            if ($this->hasParameter('export')) {
                $this->export($data, $this->getParameter('export'));
            } else {
                $this->export($data, $this->getArgument());
            }

            return true;
        }

        $this->throwError('format');

        return false;
    }
}
