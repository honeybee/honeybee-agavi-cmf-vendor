<?php

namespace Honeybee\FrameworkBinding\Agavi;

use Psr\Log\LoggerInterface;
use Honeybee\EnvironmentInterface;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\FrameworkBinding\Agavi\User\AclSecurityUser;

class Environment implements EnvironmentInterface
{
    protected $user;

    protected $config;

    protected $logger;

    public function __construct(
        AclSecurityUser $user,
        ConfigInterface $config,
        LoggerInterface$logger
    ) {
        $this->user = $user;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getUser()
    {
        return $this->user;
    }
}
