<?php

namespace Honeygavi\CodeGen\Skeleton;

use Symfony\Component\Finder\Finder;
use AgaviConfig;

/**
 * Finder that searches for skeletons in the given or default locations.
 */
class SkeletonFinder
{
    const VALIDATION_FILE = 'skeleton_parameters.validate.xml';

    protected $lookup_paths = [];

    /**
     * Finds a skeleton by name. Initializes itself with the honeybee
     * default skeleton lookup paths if none are given.
     *
     * @param array $lookup_paths folders that contain skeleton directories
     */
    public function __construct(array $lookup_paths = [])
    {
        if (empty($lookup_paths)) {
            $this->lookup_paths = AgaviConfig::get('core.skeleton_dirs', __DIR__);
        }
    }

    /**
     * Returns an array of found skeletons with that name in configured or
     * given locations.
     *
     * @return Symfony\Component\Finder\SplFileInfo instance of the first found skeleton folder
     *
     * @throws SkeletonNotFoundException when no folder of that name exists in the lookup locations
     */
    public function findByName($skeleton_name, array $locations = [])
    {
        if (empty($locations)) {
            $locations = $this->lookup_paths;
        }

        $finder = Finder::create()->directories()->depth(0)->name($skeleton_name)->sortByName()->in($locations);

        $skeletons = iterator_to_array($finder, true);

        if (empty($skeletons)) {
            throw new SkeletonNotFoundException(
                sprintf(
                    'Skeleton "%s" not found in directories: %s',
                    $skeleton_name,
                    implode(', ', $locations)
                )
            );
        }

        return reset($skeletons);
    }

    /**
     * Returns an array of all found skeletons in the configured or given
     * locations.
     *
     * @return array with Symfony\Component\Finder\SplFileInfo instances of skeleton folders
     */
    public function findAll(array $locations = [])
    {
        if (empty($locations)) {
            $locations = $this->lookup_paths;
        }

        $finder = Finder::create()->directories()->depth(0)->sortByName()->in($locations);

        return iterator_to_array($finder, true);
    }

    /**
     * Returns an array of all found skeleton validation files.
     *
     * @return array with skeleton_path => validation_file_path entries
     */
    public function findAllValidationFiles(array $locations = [])
    {
        $validation_files = [];

        foreach ($this->findAll($locations) as $skeleton_name => $skeleton_folder) {
            $validation_file = $skeleton_folder->getRealpath() . DIRECTORY_SEPARATOR . self::VALIDATION_FILE;
            if (is_readable($validation_file)) {
                $validation_files[$skeleton_name] = $validation_file;
            }
        }

        return $validation_files;
    }
}
