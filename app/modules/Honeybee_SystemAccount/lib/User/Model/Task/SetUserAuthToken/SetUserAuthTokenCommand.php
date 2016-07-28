<?php

namespace Honeybee\SystemAccount\User\Model\Task\SetUserAuthToken;

use Assert\Assertion;
use Honeybee\Model\Command\AggregateRootCommand;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;

class SetUserAuthTokenCommand extends AggregateRootCommand
{
    protected $auth_token;

    protected $token_expire_date;

    public function getEventClass()
    {
        return UserAuthTokenSetEvent::CLASS;
    }

    public function getAffectedAttributeNames()
    {
        return [ 'auth_token', 'token_expire_date' ];
    }

    public function getAuthToken()
    {
        return $this->auth_token;
    }

    public function getTokenExpireDate()
    {
        return $this->token_expire_date;
    }

    protected function guardRequiredState()
    {
        parent::guardRequiredState();

        Assertion::notNull($this->auth_token);
        Assertion::date($this->token_expire_date, DATE_ISO8601);
    }

    public function conflictsWith(AggregateRootEventInterface $event, array &$conflicting_changes = [])
    {
        return false;
    }
}
