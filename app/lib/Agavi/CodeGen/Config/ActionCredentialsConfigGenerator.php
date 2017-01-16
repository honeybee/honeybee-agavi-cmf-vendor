<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

use AgaviConfig;
use Honeybee\Infrastructure\Template\Twig\TwigRenderer;

class ActionCredentialsConfigGenerator implements ConfigGeneratorInterface
{
    const TEMPLATE = 'action_credentials.php.twig';

    const CONFIG_NAME = 'action_credentials.php';

    public function generate($name, array $scopes)
    {
        $creds = [];

        $twig_renderer = TwigRenderer::create(
            [
                'twig_options' => [ 'autoescape' => false ],
                'template_paths' => [
                    AgaviConfig::get('core.template_dir'),
                    AgaviConfig::get('core.honeybee_template_dir'),
                    __DIR__ . DIRECTORY_SEPARATOR . 'templates',
                ]
            ]
        );

        $target_path = AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR;
        $target_path .= 'includes' . DIRECTORY_SEPARATOR . $this->getConfigFileName();

        $twig_renderer->renderToFile($this->getTemplateName(), $target_path, [ 'credentials' => $scopes ]);

        return [];
    }

    protected function getConfigFileName()
    {
        return self::CONFIG_NAME;
    }

    protected function getTemplateName()
    {
        return self::TEMPLATE;
    }
}
