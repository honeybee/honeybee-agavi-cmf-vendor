<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Common\Error\RuntimeError;
use AgaviValidator;

class AggregateRootTypeValidator extends AgaviValidator
{
    protected function validate()
    {
        try {
            $aggregate_root_type_name = (string)$this->getData($this->getArgument());
            $service_locator = $this->getContext()->getServiceLocator();
            $aggregate_root_type = $service_locator->getAggregateRootTypeMap()->getItem($aggregate_root_type_name);
        } catch (RuntimeError $err_not_found) {
            $this->throwError();
            return false;
        }

        $this->export($aggregate_root_type, $this->getArgument());

        return true;
    }
}
