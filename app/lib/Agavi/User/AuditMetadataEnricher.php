<?php

namespace Honeygavi\Agavi\User;

use Honeybee\EnvironmentInterface;
use Honeybee\Infrastructure\Command\Metadata;
use Honeybee\Infrastructure\Command\MetadataEnricherInterface;

class AuditMetadataEnricher implements MetadataEnricherInterface
{
    protected $environment;

    public function __construct(EnvironmentInterface $environment)
    {
        $this->environment = $environment;
    }

    public function enrich(Metadata $metadata)
    {
        $user = $this->environment->getUser();

        $identifier = $user->getAttribute('identifier');
        $identifier = $identifier ?: $user->getAttribute('login');

        $metadata->setItem('user', $identifier);
        $metadata->setItem('role', $user->getRoleId());

        return $metadata;
    }
}
