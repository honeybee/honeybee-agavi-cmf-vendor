<?php

namespace Honeybee\SystemAccount\User\Model\Task\SetUserAuthToken;

use Honeybee\Model\Command\AggregateRootCommand;
use Honeybee\Model\Event\AggregateRootEventInterface;

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

        assert($this->auth_token !== null, '"auth_token" is set');
        assert($this->token_expire_date !== null, '"token_expire_date" is set');
    }

    public function conflictsWith(AggregateRootEventInterface $event, array &$conflicting_changes = [])
    {
        return false;
    }
}
