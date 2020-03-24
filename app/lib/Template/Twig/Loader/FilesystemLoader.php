<?php

namespace Honeygavi\Template\Twig\Loader;

use Twig\Loader\FilesystemLoader as BaseFilesystemLoader;
use Twig\Error\LoaderError;

/**
 * Extends the Twig_Loader_Filesystem class to support whitelisting of certain
 * file template extensions. It allows to use a scope for cache key generation
 * as well (to prevent reuse of templates within the same php process).
 *
 * @see https://github.com/twigphp/twig/issues/1438
 */
class FilesystemLoader extends BaseFilesystemLoader
{
    /**
     * Default string used as scope to append to cache key class name.
     */
    public const SCOPE_DEFAULT = 'default';

    /**
     * @var array allowed file template extensions
     */
    protected $allowed_extensions = null;

    /**
     * @var string scope for cache key generation
     */
    protected $scope = self::SCOPE_DEFAULT;

    /**
     * @param array $extensions allowed file template extensions
     */
    public function setAllowedExtensions(array $extensions): void
    {
        $this->allowed_extensions = $extensions;
    }

    /**
     * @param array $extensions allowed file template extensions
     */
    public function addAllowedExtensions(array $extensions): void
    {
        $this->allowed_extensions = \array_merge($this->allowed_extensions, $extensions);
    }

    /**
     * @return boolean true if allowed extensions have been set; false otherwise.
     */
    public function hasAllowedExtensions(): bool
    {
        return (null !== $this->allowed_extensions);
    }

    /**
     * Returns a cache key that uses the internal scope for cache key creation.
     *
     * @see https://github.com/fabpot/Twig/issues/1438
     */
    public function getCacheKey(string $name): string
    {
        return $this->findTemplate($name) . '_' . $this->scope;
    }

    /**
     * Sets the scope that is used within the cache key generation.
     *
     * @param string $scope
     */
    public function setScope($scope): void
    {
        $this->scope = \trim($scope);
    }

    /**
     * @return string current scope used in cache generation
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Only delegate to parent::findTemplate if name has an allowed extension.
     *
     * @param string $name
     * @param bool $throw
     * @return string|null
     * @throws LoaderError
     */
    protected function findTemplate(string $name, bool $throw = true): ?string
    {
        if ($this->hasAllowedExtensions()) {
            $this->checkAllowedExtensions($name);
        }

        return parent::findTemplate($name, $throw);
    }

    /**
     * @param string $name name of template to load
     *
     * @return boolean true if name matches the internally set allowed file extensions
     *
     * @throws LoaderError
     */
    protected function checkAllowedExtensions(string $name): bool
    {
        $allowed = false;

        foreach ($this->allowed_extensions as $extension) {
            if ($extension === \substr($name, -mb_strlen($extension))) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $allowed_extensions_hint = '["' . \implode('", "', $this->allowed_extensions) . '"]';

            if (empty($this->allowed_extensions)) {
                $allowed_extensions_hint = '[]';
            }

            throw new LoaderError(
                \sprintf(
                    'Given template "%s" does not have an allowed file extension. Allowed are: %s.',
                    $name,
                    $allowed_extensions_hint
                )
            );
        }

        return true;
    }
}
