<?php

use Honeygavi\Agavi\App\Base\Action;
use Honeygavi\Agavi\CodeGen\Config\AggregateRootTypeMapGenerator;
use Honeygavi\Agavi\CodeGen\Config\ConfigurationScanner;
use Honeygavi\Agavi\CodeGen\Config\DefaultXmlConfigGenerator;
use Honeygavi\Agavi\CodeGen\Config\NamespaceAutoloadGenerator;
use Honeygavi\Agavi\CodeGen\Config\ProjectionTypeMapGenerator;
use Honeygavi\Agavi\CodeGen\Config\RoutingXmlConfigGenerator;
use Honeygavi\Agavi\CodeGen\Config\SkeletonValidationMapGenerator;
use Honeygavi\Agavi\CodeGen\Config\WorkflowsXmlConfigGenerator;

class Honeybee_Core_Util_BuildConfigAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $included_files = [];

        // bootstrap the agavi build env, so we can use the autoloader code
        // and have the AgaviModuleCheck class available.
        $root_dir = dirname(AgaviConfig::get('core.app_dir'));
        require($root_dir . '/vendor/honeybee/agavi/src/build/agavi/build.php');
        AgaviBuild::bootstrap();

        $scanner = new ConfigurationScanner();
        foreach ($scanner->scan() as $name => $files) {
            $generator = null;
            switch ($name) {
                case 'routing':
                    $generator = new RoutingXmlConfigGenerator();
                    break;

                case 'autoload_namespaces':
                    $generator = new NamespaceAutoloadGenerator();
                    break;

                case 'aggregate_root_type_map':
                    $generator = new AggregateRootTypeMapGenerator();
                    break;

                case 'projection_type_map':
                    $generator = new ProjectionTypeMapGenerator();
                    break;

                case 'skeleton_map':
                    $generator = new SkeletonValidationMapGenerator();
                    break;

                case 'workflows':
                    $generator = new WorkflowsXmlConfigGenerator();
                    break;

                default:
                    $generator = new DefaultXmlConfigGenerator();
                    break;
            }

            $included_files = array_merge($included_files, $generator->generate($name, $files));
        }

        $this->setAttribute('includes', $included_files);

        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
