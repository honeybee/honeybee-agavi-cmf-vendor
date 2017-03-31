<?php

namespace Honeygavi\Agavi\Renderer\Twig;

use Honeygavi\Agavi\Filter\AssetCompiler;
use Twig_Extension;
use Twig_Function_Method;

class ModuleAssetsExtension extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'embed_all_main_modules' => new Twig_Function_Method($this, 'embedAllMainModules'),
        );
    }

    public function embedAllMainModules()
    {
        $main_modules_for_rjs = '';

        $module_dirs = AssetCompiler::getAvailableModuleDirectories();
        foreach ($module_dirs as $module_path) {
            $module_name = basename($module_path);
            if (is_readable($module_path . "/assets/AllModules.js")) {
                if (empty($main_modules_for_rjs)) {
                    $main_modules_for_rjs .= "{name: \"$module_name/AllModules\"}";
                } else {
                    $main_modules_for_rjs .= ",{name: \"$module_name/AllModules\"}";
                }
            }
        }

        return $main_modules_for_rjs;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string extension name.
     */
    public function getName()
    {
        return 'ModuleAssets';
    }
}
