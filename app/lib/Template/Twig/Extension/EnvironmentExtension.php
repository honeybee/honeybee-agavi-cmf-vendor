<?php

namespace Honeygavi\Template\Twig\Extension;

use Honeybee\EnvironmentInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension that wraps the EnvironmentInterface methods to make them available in twig templates.
 */
class EnvironmentExtension extends AbstractExtension
{
    protected $environment;

    public function __construct(EnvironmentInterface $environment)
    {
        $this->environment = $environment;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getEnvironment', function () {
                return $this->getEnvironment();
            }),
        ];
    }

    /**
     * @return EnvironmentInterface current environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string extension name.
     */
    public function getName()
    {
        return static::CLASS;
    }
}
