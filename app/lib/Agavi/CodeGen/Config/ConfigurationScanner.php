<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

use AgaviConfig;
use AgaviModuleFilesystemCheck;
use AgaviToolkit;
use DirectoryIterator;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton\SkeletonFinder;
use Symfony\Component\Finder\Finder;

class ConfigurationScanner
{
    protected static $supported_module_specific_configs = [
        'access_control',
        'activities',
        'autoload',
        'commands',
        'connections',
        'data_access',
        'events',
        'filesystems',
        'fixture',
        'jobs',
        'local_configuration',
        'logging',
        'mail',
        'migration',
        'navigation',
        'process',
        'services',
        'settings',
        'translation',
        'view_configs',
        'view_templates',
        'workflows'
    ];

    protected static $supported_module_variant_configs = [
        'data_access',
        'settings'
    ];

    protected static $supported_action_specific_configs = [
        'activities',
        'view_templates',
        'view_configs'
    ];

    public function scan()
    {
        $configs_to_include = [
            'aggregate_root_type_map' => [],
            'autoload_namespaces' => [],
            'projection_type_map' => [],
            'routing' => [ 'standard' => [], 'honeybee_modules' => [] ]
        ];
        foreach (self::$supported_module_specific_configs as $config_name) {
            $configs_to_include[$config_name] = [];
        }

        $directory_iterator = new DirectoryIterator(AgaviConfig::get('core.module_dir'));

        foreach ($directory_iterator as $module_directory) {
            if ($module_directory->isDot()) {
                continue;
            }
            $check = new AgaviModuleFilesystemCheck;
            $check->setConfigDirectory('config');
            $check->setPath($module_directory->getPathname());
            if (!$check->check()) {
                continue;
            }

            $module_dir = $module_directory->getPathname();
            // scan for supported module specific config files in the "config" folder of the module
            $xml_config_finder = new Finder;
            foreach ($xml_config_finder->files()->name('*.xml')->in($module_dir . '/config') as $xml_config_file) {
                $config_name = str_replace('.xml', '', basename($xml_config_file->getRelativePathname()));
                if (in_array($config_name, self::$supported_module_specific_configs)) {
                    $configs_to_include[$config_name][] = $xml_config_file->getPathname();
                } else {
                    foreach (self::$supported_module_variant_configs as $variant_config) {
                        if (strpos($config_name, $variant_config) === 0) {
                            $configs_to_include[$variant_config][] = $xml_config_file->getPathname();
                            break;
                        }
                    }
                }
            }

            // scan for supported action specific config files in the "impl" folder of the module
            $action_config_finder = new Finder;
            $action_config_finder->files()
                ->name('*.xml')
                ->notName('#\.validate\.xml$#') // agavi validation configs
                ->notName('#\.cache\.xml$#') // agavi caching configs
                ->in($module_dir . '/impl');

            foreach ($action_config_finder as $file) {
                // the supported config name needs to be "view_configs", while the
                // relativePathname is more like "ItemList/ItemList.view_configs.xml"
                $config_name = str_replace('.xml', '', $file->getFilename()); // "ItemList.view_configs.xml"
                $short_action_name = AgaviToolkit::extractShortActionName($file->getRelativePath()); // "ItemList"
                $config_name = str_replace($short_action_name . '.', '', $config_name); // remove "ItemList."
                if (in_array($config_name, self::$supported_action_specific_configs)) {
                    $configs_to_include[$config_name][] = $file->getPathname();
                }
            }

            // scan for aggregate-roots and their projections
            $entity_found = false;
            $schema_finder = new Finder;
            $schema_finder->files()->name('aggregate_root.xml')->in($module_dir . '/config');
            foreach ($schema_finder as $aggregate_root_schema_file) {
                if (!$entity_found) {
                    $entity_found = true;
                }
                $module_name = basename($module_directory);
                $schema_name = $aggregate_root_schema_file->getBasename('.xml');
                if (!in_array($module_name, $configs_to_include['routing']['honeybee_modules'])) {
                    $configs_to_include['routing']['honeybee_modules'][] = $module_name;
                }
                $configs_to_include['aggregate_root_type_map'][] = $aggregate_root_schema_file->getRealPath();
                $projections_directory = sprintf('%s/projection/', dirname($aggregate_root_schema_file->getRealPath()));
                foreach ((new Finder())->files()->name('*.xml')->in($projections_directory) as $entity_schema_file) {
                    $configs_to_include['projection_type_map'][] = $entity_schema_file->getRealPath();
                }
            }

            // add non-honeybee module routings separately
            $routing_config_path = $module_dir . '/config/routing.xml';
            if (!$entity_found && is_readable($routing_config_path)) {
                $configs_to_include['routing']['standard'][] = $routing_config_path;
            }

            // ... then check common module namespaces
            $module_manifest = $module_dir . DIRECTORY_SEPARATOR . 'module.ini';
            if (is_readable($module_manifest)) {
                $module_manifest = parse_ini_file($module_manifest);
                $module_namespace = $module_manifest['vendor'] . '\\' . $module_manifest['package'];
                $configs_to_include['autoload_namespaces'][] = [
                    'namespace' => $module_namespace,
                    'directory' => $module_dir . str_replace('/', DIRECTORY_SEPARATOR, '/lib/')
                ];
            }
        }

        $configs_to_include['skeleton_map'] = $this->findSkeletonValidationConfigs();

        return $configs_to_include;
    }

    protected function findSkeletonValidationConfigs()
    {
        $finder = new SkeletonFinder;

        return $finder->findAllValidationFiles();
    }
}
