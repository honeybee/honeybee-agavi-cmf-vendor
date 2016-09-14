<?php

namespace Honeybee\SystemAccount\User\Model\Task\SetUserAuthToken;

use Honeybee\SystemAccount\User\Model\Aggregate\UserType;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Infrastructure\Event\Bus\EventBusInterface;
use Honeybee\Model\Command\AggregateRootCommandHandler;
use Honeybee\Infrastructure\Command\CommandInterface;
use Honeybee\Infrastructure\DataAccess\DataAccessServiceInterface;
use Honeybee\Infrastructure\Workflow\WorkflowServiceInterface;
use Psr\Log\LoggerInterface;

class SetUserAuthTokenCommandHandler extends AggregateRootCommandHandler
{
    public function __construct(
        UserType $user_type,
        DataAccessServiceInterface $data_access_service,
        EventBusInterface $event_bus,
        WorkflowServiceInterface $workflow_service,
        LoggerInterface $logger
    ) {
        parent::__construct($user_type, $data_access_service, $event_bus, $workflow_service, $logger);
    }

    protected function doExecute(CommandInterface $command, AggregateRootInterface $user)
    {
        return $user->enablePasswordReset($command);
    }
}
