<?php

namespace Honeygavi\Validator;

use Honeybee\Model\Aggregate\AggregateRootInterface;

class MoveAggregateRootNodeCommandValidator extends AggregateRootCommandValidator
{
    protected function getValidatedAggregateRootCommandPayload(AggregateRootInterface $aggregate_root)
    {
        $attribute = $this->getAggregateRootType()->getAttribute('parent_node_id');

        list($is_valid, $value_holder) = $this->sanitizeAttributePayload(
            $attribute,
            $this->getData($attribute->getName())
        );

        if (!$is_valid) {
            $this->throwError('invalid_parent_id');
            return false;
        }

        return [ $attribute->getName() =>  $value_holder->getValue() ];
    }
}
