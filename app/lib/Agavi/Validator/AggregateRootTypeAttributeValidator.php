<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviValidator;
use Trellis\Runtime\Attribute\AttributeInterface;
use Exception;
use Honeybee\Common\Error\Error;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\Logging\LogTrait;

/**
 * Validates and exports the attribute for the given attribute path
 * of the configured aggregate_root_type.
 */
class AggregateRootTypeAttributeValidator extends AgaviValidator
{
    use LogTrait;

    protected $aggregate_root_type;

    protected function validate()
    {
        if ($this->hasMultipleArguments()) {
            throw new Error('Only a single argument is supported on this validator.');
        }

        $attribute_path = $this->getData($this->getArgument());

        if ($attribute_path === null) {
            $this->throwError('no_value');
            return false;
        }

        $art = $this->getAggregateRootType();

        $attribute = null;
        try {
            $attribute = $art->getAttribute($attribute_path);
        } catch (Exception $e) {
            $this->logInfo(
                'Attribute path specified for AggregateRootType',
                $art->getName(),
                'does not exist:',
                $attribute_path
            );
            $this->throwError('invalid_attribute_path');
            return false;
        }

        if (!$attribute instanceof AttributeInterface) {
            $this->logError('Attribute returned from AggregateRootType does not implement AttributeInterface');
            $this->throwError('unknown_attribute_implementation');
            return false;
        }

        $this->export($attribute);

        return true;
    }

    protected function getAggregateRootType()
    {
        if (!$this->aggregate_root_type) {
            if (!$this->hasParameter('aggregate_root_type')) {
                throw new RuntimeError('Missing required parameter "aggregate_root_type".');
            }

            $aggregate_root_type = $this->getParameter('aggregate_root_type');
            $this->aggregate_root_type = $this->getServiceLocator()->getAggregateRootTypeByPrefix($aggregate_root_type);
        }

        return $this->aggregate_root_type;
    }

    protected function getServiceLocator()
    {
        return $this->getContext()->getServiceLocator();
    }
}
