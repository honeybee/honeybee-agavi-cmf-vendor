<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandBuilder;

class SetUserPasswordCommandValidator extends AggregateRootCommandValidator
{
    protected function getCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        // password hash provided by PasswordValidator
        return $request_payload;
    }

    protected function buildCommand(array $command_values, AggregateRootInterface $aggregate_root)
    {
        $password_argument = $this->getParameter('password_argument', 'password');

        if (!isset($command_values[$password_argument])) {
            $this->throwError('missing_password');
            return false;
        }

        $result = (new AggregateRootCommandBuilder($aggregate_root->getType(), $this->getCommandImplementor()))
            ->fromEntity($aggregate_root)
            ->withPasswordHash($command_values[$password_argument])
            ->build();

        return $this->validateBuildResult($result);
    }
}
