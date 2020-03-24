<?php

namespace Honeygavi\Template\Twig\Loader;

use MtHaml\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Example integration of MtHaml with Twig, by proxying the Loader
 *
 * This loader will parse Twig templates as HAML if their filename end with
 * `.haml`, or if the code starts with `{% haml %}`.
 */
class MtHamlTwigLoader implements LoaderInterface
{
    protected $env;
    protected $loader;

    public function __construct(Environment $env, LoaderInterface $loader)
    {
        $this->env = $env;
        $this->loader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext(string $name): Source
    {
        $context = $this->loader->getSourceContext($name);
        $source = $this->renderHaml($name, $context->getCode());
        return new Source($source, $context->getName(), $context->getPath());
    }

    protected function renderHaml($name, $code)
    {
        if ('haml' === \pathinfo($name, PATHINFO_EXTENSION)) {
            $code = $this->env->compileString($code, $name);
        } elseif (\preg_match('#^\s*{%\s*haml\s*%}#', $code, $match)) {
            $padding = \str_repeat(' ', \strlen($match[0]));
            $code = $padding . \substr($code, \strlen($match[0]));
            $code = $this->env->compileString($code, $name);
        }

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey(string $name): string
    {
        return $this->loader->getCacheKey($name);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh(string $name, int $time): bool
    {
        return $this->loader->isFresh($name, $time);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name)
    {
        return $this->loader->exists($name);
    }
}
