<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\FrameworkBinding\Agavi\Filter\ResourceCompiler;

class Honeybee_Core_Util_WatchScssAction extends Action
{
    public function executeWrite(\AgaviRequestDataHolder $request_data)
    {
        try {
            $style = $request_data->getParameter('style', AgaviConfig::get('sass.style', 'compressed'));
            $theme_name = $request_data->getParameter('theme', AgaviConfig::get('themes.default', 'honeybee-minimal'));

            $packer = new ResourceCompiler();

            $packer->symlinkModuleResources();

            $processes = array();
            if (!$request_data->getParameter('no-theme', false)) {
                $processes["Theme $theme_name"] = $packer->getWatchThemeProcess($theme_name, $style);
            }

            if (!$request_data->getParameter('no-modules', false)) {
                $processes = array_merge($processes, $packer->getWatchModuleStylesProcesses($style));
            }

            $this->setAttribute('processes', $processes);
        } catch (\Exception $e) {
            $this->setAttribute('error', $e->getMessage());
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
