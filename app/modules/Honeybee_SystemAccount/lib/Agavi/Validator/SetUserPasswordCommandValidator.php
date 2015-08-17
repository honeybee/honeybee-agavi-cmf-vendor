<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;

class SetUserPasswordCommandValidator extends AggregateRootCommandValidator
{
    protected function getValidatedAggregateRootCommandPayload(AggregateRootInterface $aggregate_root)
    {
        $password_argument = $this->getParameter('password_argument', 'password');

        return [ 'password_hash' => $this->getData($password_argument) ];
    }
}
