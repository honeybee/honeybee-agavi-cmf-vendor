<?php

use Honeygavi\App\Base\Action;
use Honeygavi\Filter\AssetCompiler;
use Honeygavi\Renderer\ModuleTemplateRenderer;

class Honeybee_Core_Util_CompileJsAction extends Action
{
    public function executeWrite(\AgaviRequestDataHolder $request_data)
    {
        $report = array();
        $success = false;
        try {
            $optimize_style = $request_data->getParameter(
                'optimize',
                AgaviConfig::get('requirejs.optimize_style', 'uglify2')
            );

            $buildfile_path = $request_data->getParameter(
                'buildfile',
                AgaviConfig::get(
                    'requirejs.buildfile_path',
                    AgaviConfig::get('core.pub_dir') . "/static/buildconfig.js"
                )
            );

            $packer = new AssetCompiler();

            // just in case
            $packer->symlinkModuleAssets();

            // render buildconfig.js and put it into the target location for compilation
            $template_service = new ModuleTemplateRenderer();
            $buildconfig_content = $template_service->render('rjs/buildconfig.js');
            $success = file_put_contents($buildfile_path, $buildconfig_content, LOCK_EX);
            if (!$success) {
                $this->setAttribute('error', 'Could not write file: ' . $buildfile_path);
            }

            $success = $packer->compileJs($buildfile_path, $optimize_style, $report);

            $this->setAttribute('report', $report);
        } catch (\Exception $e) {
            $this->setAttribute('error', $e->getMessage());
            return 'Error';
        }

        if (!$success) {
            return 'Error';
        }

        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
