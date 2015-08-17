<?php

namespace Honeybee\SystemAccount\User\Projection\EventHandler;

use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\DataAccess\DataAccessServiceInterface;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Projection\EventHandler\ProjectionUpdater;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\SystemAccount\MailService;
use Honeybee\SystemAccount\User\Model\Task\CreateUser\UserCreatedEvent;
use Honeybee\SystemAccount\User\Model\Task\SetUserAuthToken\UserAuthTokenSetEvent;
use Honeybee\SystemAccount\User\Projection\Standard\User;
use Psr\Log\LoggerInterface;

class UserProjectionUpdater extends ProjectionUpdater
{
    protected $mail_service;

    public function __construct(
        ConfigInterface $config,
        LoggerInterface $logger,
        DataAccessServiceInterface $data_access_service,
        ProjectionTypeMap $projection_type_map,
        AggregateRootTypeMap $aggregate_root_type_map,
        MailService $mail_service
    ) {
        parent::__construct(
            $config,
            $logger,
            $data_access_service,
            $projection_type_map,
            $aggregate_root_type_map
        );

        $this->mail_service = $mail_service;
    }

    protected function afterUserAuthTokenSet(UserAuthTokenSetEvent $event, User $user)
    {
        if ($this->config->get('send_emails', false)) {
            $event_data = $event->getData();
            $this->mail_service->sendUserPasswordResetEmail($event_data['auth_token'], $user);
        }
    }

    protected function afterUserCreated(UserCreatedEvent $event, User $user)
    {
        if ($this->config->get('send_emails', false)) {
            $event_data = $event->getData();
            $this->mail_service->sendUserPasswordResetEmail($event_data['auth_token'], $user);
        }
    }
}
