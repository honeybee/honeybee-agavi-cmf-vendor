<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;
use DateInterval;
use DateTime;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootTypeCommandValidator;

class CreateUserCommandValidator extends AggregateRootTypeCommandValidator
{
    protected function getValidatedAggregateRootCommandPayload(AggregateRootInterface $aggregate_root)
    {
        $expire_date = new DateTime();
        $expire_date->add(new DateInterval('PT20M')); // 20 minutes

        $retval = parent::getValidatedAggregateRootCommandPayload($aggregate_root);

        $command_payload =& $retval['values'];
        $command_payload['auth_token'] = StringToolkit::generateRandomToken();
        $command_payload['token_expire_date'] = $expire_date;
        if (!isset($command_payload['role'])) {
            $command_payload['role'] = $this->getParameter('default_role', 'administrator');
        }

        return $retval;
    }
}
