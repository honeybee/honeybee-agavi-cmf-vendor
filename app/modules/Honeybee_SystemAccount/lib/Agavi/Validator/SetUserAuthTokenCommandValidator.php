<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use DateInterval;
use DateTime;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootCommandValidator;
use Honeybee\Model\Aggregate\AggregateRootInterface;

class SetUserAuthTokenCommandValidator extends AggregateRootCommandValidator
{
    protected function getCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        $expire_date = new DateTime;
        $expire_date->add(new DateInterval('PT20M')); // 20 minutes

        return [
            'auth_token' => StringToolkit::generateRandomToken(),
            'token_expire_date' => $expire_date->format(DATE_ISO8601)
        ];
    }
}
