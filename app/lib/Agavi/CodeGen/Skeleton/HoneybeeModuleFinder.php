<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton;

use AgaviConfig;
use Honeybee\Common\Error\RuntimeError;
use Symfony\Component\Finder\Finder;

/**
 * Finder that searches for Honeybee modules in the current application's
 * module directory or the given locations.
 */
class HoneybeeModuleFinder
{
    protected $lookup_paths = [];

    /**
     * Finds a module by name. Initializes itself with the application's module directory.
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
     * Returns an array of found modules.
     *
     * @return Symfony\Component\Finder\SplFileInfo instance of the first found module folder
     *
     * @throws RuntimeError when no folder of that name exists in the lookup locations
     */
    public function findByName($name, array $locations = [])
    {
        if (empty($locations)) {
            $locations = $this->lookup_paths;
        }

        $finder = Finder::create()->directories()->depth(0)->name($name)->sortByName()->in($locations);

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

        return reset($results);
    }

    /**
     * @return array with Symfony\Component\Finder\SplFileInfo instances
     */
    public function findAll(array $locations = [])
    {
        if (empty($locations)) {
            $locations = $this->lookup_paths;
        }

        $finder = Finder::create()->directories()->depth(0)->sortByName()->in($locations);

        return iterator_to_array($finder, true);
    }
}
