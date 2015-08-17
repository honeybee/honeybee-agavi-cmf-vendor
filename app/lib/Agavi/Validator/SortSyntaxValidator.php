<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviValidator;

/**
 * Allows 'sort' argument that looks like: 'field1:asc,field2:desc'.
 */
class SortSyntaxValidator extends AgaviValidator
{
    protected function validate()
    {
        if ($this->hasMultipleArguments()) {
            $this->throwError('multiple_arguments');
            return false;
        }

        // this is done to allow to set a default argument in a validator_definition
        $argument_name = $this->getArgument();
        if (false === $argument_name) {
            $argument_name = $this->getParameter('argument_name');
            $this->arguments = array($argument_name);
        }

        $sort_value = $this->getData($argument_name);
        if (!$sort_value) {
            $sort_value = $this->getParameter('value'); // no sort value provided in request, but hopefully as parameter
        }

        $sort_value = trim($sort_value);

        // check if the provided fqdn is actually available
        if (empty($sort_value)) {
            $this->throwError('empty');
            return false;
        }

        $sort = [];

        $sort_fields = explode(',', $sort_value);
        foreach ($sort_fields as $sort_field) {
            $temp = explode(':', $sort_field);
            if (count($temp) !== 2) {
                $this->throwError('field_syntax');
                return false;
            }
            $matches = [];
            if (!preg_match('/^([\w\.]+):(asc|desc)$/', $sort_field, $matches)) {
                $this->throwError('syntax');
                return false;
            }

            $sort[$matches[1]] = $matches[2];
        }

        $this->export($sort_value, $this->getParameter('export', $argument_name));
        $this->export($sort, 'sort_fields');

        return true;
    }
}
