<?php

namespace Honeybee\SystemAccount\User\Model\Task\SetUserPassword;

use Honeybee\Model\Command\AggregateRootCommand;
use Honeybee\Model\Event\AggregateRootEventInterface;

class SetUserPasswordCommand extends AggregateRootCommand
{
    protected $password_hash;

    public function getEventClass()
    {
        return UserPasswordSetEvent::CLASS;
    }

    public function getAffectedAttributeNames()
    {
        return [ 'password_hash' ];
    }

    public function getPasswordHash()
    {
        return $this->password_hash;
    }

    protected function guardRequiredState()
    {
        parent::guardRequiredState();

        assert($this->password_hash !== null, '"password_hash" is set');
    }

    public function conflictsWith(AggregateRootEventInterface $event, array &$conflicting_changes = [])
    {
        return false;
    }
}
