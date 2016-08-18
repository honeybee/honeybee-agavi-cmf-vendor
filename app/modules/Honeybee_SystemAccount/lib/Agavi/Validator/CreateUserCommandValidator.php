<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use DateInterval;
use DateTimeImmutable;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootTypeCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;

class CreateUserCommandValidator extends AggregateRootTypeCommandValidator
{
    protected function getValidatedCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        $command_values = (array)parent::getValidatedCommandValues($request_payload, $aggregate_root);

        $expire_date = (new DateTimeImmutable)->add(new DateInterval('PT20M'));

        $command_values['auth_token'] = StringToolkit::generateRandomToken();
        $command_values['token_expire_date'] = $expire_date;
        if (!isset($command_values['role'])) {
            $command_values['role'] = $this->getParameter('default_role', 'non-privileged');
        }

        return $command_values;
    }
}
