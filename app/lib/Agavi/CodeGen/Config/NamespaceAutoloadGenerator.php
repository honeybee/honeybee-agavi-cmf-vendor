<?php

namespace Honeygavi\Agavi\CodeGen\Config;

use AgaviConfig;
use Honeygavi\Template\Twig\TwigRenderer;

class NamespaceAutoloadGenerator implements ConfigGeneratorInterface
{
    public function generate($name, array $package_map)
    {
        $relative_packages = [];
        $included_directories = [];

        foreach ($package_map as $package) {
            $relative_package = $package;
            $relative_package['directory'] = str_replace(
                AgaviConfig::get('core.app_dir'),
                "dirname(dirname(__DIR__)).'",
                $relative_package['directory']
            );
            $included_directories[] = $relative_package['directory'];
            $relative_package['namespace'] = var_export($relative_package['namespace'] . '\\', true);
            $relative_packages[] = $relative_package;
        }

        if (!empty($relative_packages)) {
            $autoload_path = AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR;
            $autoload_path .= 'includes' . DIRECTORY_SEPARATOR . 'autoload.php';

            $twig_renderer = TwigRenderer::create(
                [
                    'twig_options' => [ 'autoescape' => false ],
                    'template_paths' => [ __DIR__ . DIRECTORY_SEPARATOR . 'templates' ]
                ]
            );

            $twig_renderer->renderToFile('autoload.php.twig', $autoload_path, [ 'packages' => $relative_packages ]);
        }

        return $included_directories;
    }
}
