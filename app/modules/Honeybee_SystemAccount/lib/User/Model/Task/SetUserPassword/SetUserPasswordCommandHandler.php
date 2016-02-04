<?php

namespace Honeybee\SystemAccount\User\Model\Task\SetUserPassword;

use Honeybee\SystemAccount\User\Model\Aggregate\UserType;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Infrastructure\Event\Bus\EventBusInterface;
use Honeybee\Model\Command\AggregateRootCommandHandler;
use Honeybee\Infrastructure\Command\CommandInterface;
use Honeybee\Infrastructure\DataAccess\DataAccessServiceInterface;
use Psr\Log\LoggerInterface;

class SetUserPasswordCommandHandler extends AggregateRootCommandHandler
{
    public function __construct(
        UserType $user_type,
        DataAccessServiceInterface $data_access_service,
        EventBusInterface $event_bus,
        LoggerInterface $logger
    ) {
        parent::__construct($user_type, $data_access_service, $event_bus, $logger);
    }

    protected function doExecute(CommandInterface $command, AggregateRootInterface $user)
    {
        return $user->changePassword($command);
    }
}
