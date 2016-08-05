<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

use AgaviConfig;
use Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton\SkeletonFinder;
use Honeybee\Infrastructure\Template\Twig\TwigRenderer;
use Symfony\Component\Filesystem\Filesystem;

class SkeletonValidationMapGenerator implements ConfigGeneratorInterface
{
    protected $target_path;
    protected $template_path;
    protected $twig_renderer;

    public function __construct()
    {
        $this->target_path = AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR .
           'includes' . DIRECTORY_SEPARATOR . SkeletonFinder::VALIDATION_FILE;
        $this->template_path = __DIR__ . DIRECTORY_SEPARATOR . 'templates';

        $this->twig_renderer = TwigRenderer::create(
            [
                'twig_options' => [ 'autoescape' => false ],
                'template_paths' => [ $this->template_path ]
            ]
        );
    }

    public function generate($name, array $files_to_include)
    {
        // find paths relative to config directory
        $filesystem = new Filesystem;
        $config_include_dir = AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . 'includes';
        $relative_file_paths = [];
        foreach ($files_to_include as $file_to_include) {
            $relative_path = $filesystem->makePathRelative(dirname($file_to_include), $config_include_dir);
            $relative_file_paths[] = $relative_path . basename($file_to_include);
        }

        $this->twig_renderer->renderToFile(
            SkeletonFinder::VALIDATION_FILE . '.twig',
            $this->target_path,
            [ 'name' => $name, 'files' => $relative_file_paths ]
        );

        return $files_to_include;
    }
}
