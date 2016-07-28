<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use DateInterval;
use DateTimeImmutable;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandBuilder;

class SetUserAuthTokenCommandValidator extends AggregateRootCommandValidator
{
    protected function getValidatedCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        $expire_date = (new DateTimeImmutable)->add(new DateInterval('PT20M'));

        return [
            'auth_token' => StringToolkit::generateRandomToken(),
            'token_expire_date' => $expire_date->format(DATE_ISO8601)
        ];
    }

    protected function buildCommand(array $command_values, AggregateRootInterface $aggregate_root)
    {
        $result = (new AggregateRootCommandBuilder($aggregate_root->getType(), $this->getCommandImplementor()))
            ->fromEntity($aggregate_root)
            ->withTokenExpireDate($command_values['token_expire_date'])
            ->withAuthToken($command_values['auth_token'])
            ->build();

        return $this->validateBuildResult($result);
    }
}
