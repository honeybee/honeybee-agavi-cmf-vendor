<?php

namespace Honeygavi\CodeGen\Config;

use AgaviConfig;
use Honeygavi\Template\Twig\TwigRenderer;

class WorkflowsXmlConfigGenerator implements ConfigGeneratorInterface
{
    public function generate($name, array $files_to_include)
    {
        $target_path = AgaviConfig::get('core.config_dir') . '/includes/workflow_configs.php';

        $template_paths = [
            AgaviConfig::get('core.template_dir'),
            AgaviConfig::get('core.honeybee_template_dir'),
            __DIR__ . DIRECTORY_SEPARATOR . 'templates',
        ];

        $template = 'workflow_configs.php.twig';

        $twig_renderer = TwigRenderer::create(
            [
                'twig_options' => [ 'autoescape' => false ],
                'template_paths' => $template_paths
            ]
        );

        $files = array_map(function($abs_config_file_path) {
            return str_replace(
                AgaviConfig::get('core.module_dir'),
                '',
                $abs_config_file_path
            );
        }, $files_to_include);

        $twig_renderer->renderToFile($template, $target_path, [ 'files' => $files ]);

        return $files_to_include;
    }
}
