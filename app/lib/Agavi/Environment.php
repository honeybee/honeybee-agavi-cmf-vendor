<?php

namespace Honeybee\FrameworkBinding\Agavi;

use Psr\Log\LoggerInterface;
use Honeybee\EnvironmentInterface;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\FrameworkBinding\Agavi\User\AclSecurityUser;

class Environment implements EnvironmentInterface
{
    protected $user;

    protected $settings;

    protected $config;

    protected $logger;

    public function __construct(
        AclSecurityUser $user,
        SettingsInterface $settings,
        ConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->user = $user;
        $this->settings = $settings;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getSettings()
    {
        return $this->settings;
    }
}
