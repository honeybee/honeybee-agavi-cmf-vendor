<?php

namespace Honeygavi\Validator;

use AgaviValidator;

class RoleValidator extends AgaviValidator
{
    protected function validate()
    {
        $data = (string)$this->getData($this->getArgument());

        if (!in_array($data, $this->getContext()->getUser()->getAvailableRoles(), true)) {
            $this->throwError();
            return false;
        }

        return true;
    }
}
