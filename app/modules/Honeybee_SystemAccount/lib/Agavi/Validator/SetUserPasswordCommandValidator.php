<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;

class SetUserPasswordCommandValidator extends AggregateRootCommandValidator
{
    protected function getCommandPayload(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        $password_argument = $this->getParameter('password_argument', 'password');

        if (!isset($request_payload[$password_argument])) {
            return [];
        }

        return [ 'password_hash' => $request_payload[$password_argument]) ];
    }
}
