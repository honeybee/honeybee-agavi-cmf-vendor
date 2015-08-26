<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\FrameworkBinding\Agavi\Filter\AssetCompiler;

class Honeybee_Core_Util_CompileScssAction extends Action
{
    public function executeWrite(\AgaviRequestDataHolder $request_data)
    {
        $report = array();
        try {
            $style = $request_data->getParameter('style', AgaviConfig::get('sass.style', 'compressed'));

            $packer = new AssetCompiler();

            // just in case
            $packer->symlinkModuleAssets();

            $compilation_succeeded = $packer->compileThemes($style, $report);
            $compilation_succeeded &= $packer->compileModuleStyles($style, $report);

            $this->setAttribute('report', $report);
        } catch (\Exception $e) {
            $this->setAttribute('error', $e->getMessage());
            return 'Error';
        }

        if (!$compilation_succeeded) {
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
