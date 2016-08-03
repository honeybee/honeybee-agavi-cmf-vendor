<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton;

use AgaviConfig;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\Projection\ProjectionTypeInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Trellis\CodeGen\Console\GenerateCodeCommand;

class HoneybeeResourceGenerator extends SkeletonGenerator
{
    /**
     * Creates a new generator instance.
     *
     * @param string $skeleton_name name of the skeleton
     * @param string $target_path full path to the target location
     * @param array $data variables to use as context for rendering via twig
     */
    public function __construct($skeleton_name, $target_path, array $data = [])
    {
        $data['variant'] = ProjectionTypeInterface::DEFAULT_VARIANT;

        $required_data_keys = [ 'vendor', 'package', 'resource', 'variant' ];
        foreach ($required_data_keys as $required_data_key) {
            if (!array_key_exists($required_data_key, $data)) {
                throw new RuntimeError(sprintf('A "%s" parameter must be provided', $required_data_key));
            }
        }

        $vendor = $data['vendor'];
        $package = $data['package'];
        $resource = $data['resource'];
        $variant = $data['variant'];

        $data['target_path'] = $target_path;
        $data['db_prefix'] = AgaviConfig::get('core.db_prefix');
        $data['vendor_prefix'] = strtolower($vendor);
        $data['package_prefix'] = StringToolkit::asSnakeCase($package);
        $data['resource_prefix'] = StringToolkit::asSnakeCase($resource);
        $data['variant_prefix'] = StringToolkit::asSnakeCase($variant);
        $data['timestamp'] = date('YmdHis');

        parent::__construct($skeleton_name, $target_path, $data);

        $this->overwrite_enabled = isset($data['override_files']) ? $data['override_files'] : false;
        $this->reporting_enabled = isset($data['reporting_enabled']) ? $data['reporting_enabled'] : false;
    }

    public function generate()
    {
        parent::generate();

        // Now that a new resource skeleton has now been created, we will generate
        // the aggregate root and standard projection files immediately.
        $this->executeTrellis('aggregate_root');
        $this->executeTrellis($this->data['variant_prefix']);

        // Copy Trellis ES mapping file to correction location
        $trellis_config_path = $this->getTargetPath($this->data['variant_prefix']) . $this->data['variant_prefix'] . '.ini';
        $trellis_config = parse_ini_file($trellis_config_path);
        $mapping_source_path = sprintf(
            '%s%s%s',
            dirname($trellis_config_path),
            DIRECTORY_SEPARATOR,
            $trellis_config['deploy_path']
        );

        if (!is_readable($mapping_source_path)) {
            throw new RuntimeError(sprintf('Could not find mapping source file at %s', $mapping_source_path));
        }

        $mapping_target_path = sprintf(
            '%1$s%2$smigration%2$selasticsearch%2$s%3$s_create_%4$s%2$s%5$s',
            $this->data['target_path'],
            DIRECTORY_SEPARATOR,
            $this->data['timestamp'],
            $this->data['resource_prefix'],
            str_replace('{{ timestamp }}', $this->data['timestamp'], basename($trellis_config['deploy_path']))
        );

        if (!is_writable(dirname($mapping_target_path))) {
            throw new RuntimeError(sprintf('Mapping target path is not writable for %s', $mapping_target_path));
        }

        rename($mapping_source_path, $mapping_target_path);
    }

    protected function executeTrellis($trellis_target)
    {
        $target_path = $this->getTargetPath($trellis_target);
        $trellis_schema_path = sprintf('%s%s.xml', $target_path, $trellis_target);
        $trellis_config_path = sprintf('%s%s.ini', $target_path, $trellis_target);

        $command = new GenerateCodeCommand;
        $input = new ArrayInput(
            [
                'action' => 'gen+dep',
                '--schema' => $trellis_schema_path,
                '--config' => $trellis_config_path
            ]
        );
        $output = new BufferedOutput;

        $exit_code = $command->run($input, $output);
        if ($exit_code != 0) {
            throw new RuntimeError(sprintf(
                'Trellis execution failed for target "%s" with exit code %s',
                $trellis_target,
                $exit_code
            ));
        }
    }

    protected function getTargetPath($trellis_target)
    {
        $path_suffix = $trellis_target != 'aggregate_root' ? 'projection' . DIRECTORY_SEPARATOR : '';

        return sprintf(
            '%1$s%2$sconfig%2$s%3$s%2$sentity_schema%2$s' . $path_suffix,
            $this->data['target_path'],
            DIRECTORY_SEPARATOR,
            $this->data['resource']
        );
    }
}
