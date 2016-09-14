<?php

namespace Honeybee\SystemAccount\User\Model\Task\ProceedUserWorkflow;

use Honeybee\SystemAccount\User\Model\Aggregate\UserType;
use Honeybee\Infrastructure\Event\Bus\EventBusInterface;
use Honeybee\Model\Task\ProceedWorkflow\ProceedWorkflowCommandHandler;
use Honeybee\Infrastructure\DataAccess\DataAccessServiceInterface;
use Honeybee\Infrastructure\Workflow\WorkflowServiceInterface;
use Psr\Log\LoggerInterface;

class ProceedUserWorkflowCommandHandler extends ProceedWorkflowCommandHandler
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
}
