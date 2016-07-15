<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton;

use AgaviConfig;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;

class HoneybeeModuleGenerator extends SkeletonGenerator
{
    /**
     * Creates a new generator instance.
     *
     * @param string $skeleton_name name of the skeleton
     * @param string $target_path full path to the target location (defaults to %core.module_dir%)
     * @param array $data variables to use as context for rendering via twig
     */
    public function __construct($skeleton_name, $target_path = null, array $data = [])
    {
        $required_data_keys = [ 'vendor', 'package' ];
        foreach ($required_data_keys as $required_data_key) {
            if (!array_key_exists($required_data_key, $data)) {
                throw new RuntimeError(sprintf('A "%s" parameter must be provided', $required_data_key));
            }
        }

        $vendor = $data['vendor'];
        $package = $data['package'];

        $module_name = sprintf('%s_%s', $vendor, $package);
        if (null === $target_path) {
            $target_path = AgaviConfig::get('core.module_dir') . DIRECTORY_SEPARATOR . $module_name;
        } else {
            $target_path .= DIRECTORY_SEPARATOR . $module_name;
        }

        $data['target_path'] = $target_path;
        $data['db_prefix'] = AgaviConfig::get('core.db_prefix');
        $data['vendor_prefix'] = strtolower($vendor);
        $data['package_prefix'] = StringToolkit::asSnakeCase($package);
        $data['timestamp'] = date('YmdHis');

        parent::__construct($skeleton_name, $target_path, $data);

        $this->overwrite_enabled = isset($data['override_files']) ? $data['override_files'] : false;
        $this->reporting_enabled = isset($data['reporting_enabled']) ? $data['reporting_enabled'] : false;
    }

    protected function getFolderStructure()
    {
        return [ 'impl' ];
    }
}
