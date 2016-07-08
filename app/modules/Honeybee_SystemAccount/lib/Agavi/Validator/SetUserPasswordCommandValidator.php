<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandBuilder;

class SetUserPasswordCommandValidator extends AggregateRootCommandValidator
{
    protected function getValidatedCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        // password hash provided by PasswordValidator
        $password_argument = $this->getParameter('password_argument', 'password');

        if (!isset($request_payload[$password_argument])) {
            $this->throwError('missing_password');
            return false;
        }

        return [ 'password_hash' => $request_payload[$password_argument] ];
    }

    protected function buildCommand(array $command_values, AggregateRootInterface $aggregate_root)
    {
        $result = (new AggregateRootCommandBuilder($aggregate_root->getType(), $this->getCommandImplementor()))
            ->fromEntity($aggregate_root)
            ->withPasswordHash($command_values['password_hash'])
            ->build();

        return $this->validateBuildResult($result);
    }
}
