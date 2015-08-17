<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton;

use AgaviConfig;
use Honeybee\Common\Error\RuntimeError;
use Symfony\Component\Finder\Finder;

class NewSkeletonGenerator extends SkeletonGenerator
{
    /**
     * Creates a new generator instance.
     *
     * @param string $skeleton_name name of the skeleton
     * @param string $target_path full path to the target location (defaults to %core.skeleton_dir%
     * @param array $data variables to use as context for rendering via twig
     */
    public function __construct($skeleton_name, $target_path = null, array $data = [])
    {
        if (!array_key_exists('new_skeleton_name', $data)) {
            throw new RuntimeError('A "new_skeleton_name" parameter must be provided');
        }

        if (null === $target_path) {
            $target_path = AgaviConfig::get('core.skeleton_dir') . DIRECTORY_SEPARATOR . $data['new_skeleton_name'];
        } else {
            $target_path .= DIRECTORY_SEPARATOR . $data['new_skeleton_name'];
        }

        $data['target_path'] = $target_path;

        parent::__construct($skeleton_name, $target_path, $data);
    }

    /**
     * @param string $source_path path to copy files from
     *
     * @return Finder instance configured with all files to copy from the source path
     */
    protected function getFinderForFilesToCopy($source_path)
    {
        $finder = new Finder();
        $finder->files()->in($source_path);
        return $finder;
    }

}
