<?php

namespace Honeygavi\CodeGen\Trellis;

use AgaviConfig;
use Honeybee\Common\Error\RuntimeError;
use Symfony\Component\Finder\Finder;

/**
 * Finder that searches for Trellis targets in the given
 * module directory or the given locations.
 */
class TrellisTargetFinder
{
    protected $lookup_paths = [];

    /**
     * Finds a target by name. Initializes itself with the application's module directory.
     *
     * @param array $lookup_paths folders that contain module directories
     */
    public function __construct(array $lookup_paths = [])
    {
        if (empty($lookup_paths)) {
            $this->lookup_paths = AgaviConfig::get('core.module_dir');
        }
    }

    /**
     * Returns a trellis target.
     *
     * @return Symfony\Component\Finder\SplFileInfo instance of the first found target
     *
     * @throws RuntimeError when no folder of that name exists in the lookup locations
     */
    public function findByName($name, array $locations = [])
    {
        if (empty($locations)) {
            $locations = $this->lookup_paths;
        }

        $finder = new Finder();
        $finder->files()->name($name . '.xml')->depth('>= 0')->in($locations)->sortByName();

        $results = iterator_to_array($finder, true);

        if (empty($results)) {
            throw new RuntimeError(
                sprintf(
                    'Did not find "%s" in directories: %s',
                    $name,
                    implode(', ', $locations)
                )
            );
        }

        return $results;
    }

    /**
     * @return array with Symfony\Component\Finder\SplFileInfo instances
     */
    public function findAll(array $locations = [])
    {
        if (empty($locations)) {
            $locations = $this->lookup_paths;
        }

        $finder = new Finder();
        $finder->files()->name('*.xml')->depth('>= 0')->in($locations)->sortByName();

        return iterator_to_array($finder, true);
    }
}
