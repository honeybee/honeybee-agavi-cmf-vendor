<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;

/**
 * Validator that checks if the 'value' parameter's value is a fully
 * qualified domain name and exists as a class. The parameter's value
 * is only evaluated if no argument data was provided.
 */
class ClassExistsSetValidator extends AgaviValidator
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

        $fqdn = $this->getData($argument_name);
        if (!$fqdn) {
            $fqdn = $this->getParameter('value'); // no fqdn provided in request, but hopefully as parameter
        }

        // check if the provided fqdn is actually available
        if (!class_exists($fqdn)) {
            $this->throwError('class_not_found');
            return false;
        }

        if ($this->hasParameter('implements') &&
            !in_array($this->getParameter('implements'), class_implements($fqdn))
        ) {
            $this->throwError('missing_interface');
            return false;
        }

        $this->export($fqdn, $this->getParameter('export', $argument_name));

        return true;
    }

    protected function checkAllArgumentsSet($throw_error = true)
    {
        return true;
    }
}
