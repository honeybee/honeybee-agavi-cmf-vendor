<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;

class DisplayModeValidator extends AgaviValidator
{
    const DISPLAY_MODE_TABLE = 'table';
    const DISPLAY_MODE_GRID = 'grid';

    protected function validate()
    {
        $value = $this->getData($this->getArgument());

        if (null === $value) {
            $this->export(self::DISPLAY_MODE_TABLE, $this->getParameter('export', $this->getArgument()));
            return true;
        }

        return parent::validate();
    }

    protected function checkAllArgumentsSet($throw_error = true)
    {
        return true;
    }
}
