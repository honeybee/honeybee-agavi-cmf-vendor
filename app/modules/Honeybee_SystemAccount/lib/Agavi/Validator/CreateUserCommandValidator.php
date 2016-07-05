<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use DateInterval;
use DateTime;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootTypeCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;

class CreateUserCommandValidator extends AggregateRootTypeCommandValidator
{
    protected function getCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        $command_values = parent::getCommandValues($request_payload, $aggregate_root);

        $expire_date = new DateTime;
        $expire_date->add(new DateInterval('PT20M')); // 20 minutes

        $command_values['auth_token'] = StringToolkit::generateRandomToken();
        $command_values['token_expire_date'] = $expire_date;
        if (!isset($command_values['role'])) {
            $command_values['role'] = $this->getParameter('default_role', 'administrator');
        }

        return $command_values;
    }
}
