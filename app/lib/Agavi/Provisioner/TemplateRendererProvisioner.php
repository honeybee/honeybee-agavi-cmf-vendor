<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use DirectoryIterator;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\Renderer\Twig\HoneybeeToolkitExtension;
use Honeybee\FrameworkBinding\Agavi\Renderer\Twig\MarkdownExtension;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Template\TemplateRendererInterface;
use Honeybee\Infrastructure\Template\Twig\Extension\ToolkitExtension;
use Honeybee\Infrastructure\Template\Twig\Extension\TranslatorExtension;
use Honeybee\Infrastructure\Template\Twig\Loader\FilesystemLoader;
use Honeybee\ServiceDefinitionInterface;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Extensions_Extension_Array;
use Twig_Extensions_Extension_Date;
use Twig_Extensions_Extension_Intl;
use Twig_Extensions_Extension_Text;

class TemplateRendererProvisioner extends AbstractProvisioner
{
    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $service = $service_definition->getClass();

        $state = [
            ':twig' => $this->createTwigRenderer($this->getTwigTemplateRendererSettings($provisioner_settings))
        ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(TemplateRendererInterface::CLASS, $service);
    }

    protected function createTwigRenderer(SettingsInterface $settings)
    {
        $loader = $this->createTwigLoader($settings);

        $twig = new Twig_Environment($loader, (array)$settings->get('twig_options', []));

        $twig_extensions = (array)$settings->get('twig_extensions', []);
        foreach ($twig_extensions as $extension_class) {
            if (is_object($extension_class)) {
                $twig->addExtension($extension_class);
            } else {
                $twig->addExtension(new $extension_class());
            }
        }

        return $twig;
    }

    protected function getTwigTemplateRendererSettings(SettingsInterface $provisioner_settings)
    {
        $settings = [];

        $cache = false;
        if (!AgaviConfig::get('core.debug', false)) {
            $cache = AgaviConfig::get('core.cache_dir') . DIRECTORY_SEPARATOR . 'templates_twig';
        }

        $settings['twig_options'] = [
            'autoescape' => 'html',
            'strict_variables' => false,
            'debug' => AgaviConfig::get('core.debug', false),
            'cache' => $cache
        ];

        $settings['twig_extensions'] = [
            Twig_Extensions_Extension_Text::CLASS,
            Twig_Extensions_Extension_Intl::CLASS,
            Twig_Extensions_Extension_Array::CLASS,
            Twig_Extensions_Extension_Date::CLASS,
            ToolkitExtension::CLASS,
            HoneybeeToolkitExtension::CLASS,
            TranslatorExtension::CLASS,
            MarkdownExtension::CLASS
        ];

        if ($settings['twig_options']['debug'] === true) {
            $settings['twig_extensions'][] = Twig_Extension_Debug::CLASS;
        }

        $settings['template_paths'] = [
            AgaviConfig::get('core.template_dir'),
            AgaviConfig::get('core.honeybee_template_dir')
        ];

        $settings['allowed_template_extensions'] = [
            '.twig',
            '.html'
        ];

        $settings = array_replace_recursive($settings, $provisioner_settings->toArray());

        // instantiate the wanted twig extensions here (as they might need dependencies)
        $twig_extensions = $settings['twig_extensions'];
        $settings['twig_extensions'] = [];
        foreach ($twig_extensions as $extension) {
            $settings['twig_extensions'][] = $this->di_container->make($extension);
        }

        return new Settings($settings);
    }

    protected function createTwigLoader(SettingsInterface $settings)
    {
        if (!$settings->has('template_paths')) {
            throw new RuntimeError('Missing "template_paths" settings with template lookup locations.');
        }

        $template_paths = (array)$settings->get('template_paths', []);

        $loader = new FilesystemLoader($template_paths);
        if ($settings->has('allowed_template_extensions')) {
            $loader->setAllowedExtensions((array)$settings->get('allowed_template_extensions'));
        }

        if (!$settings->has('cache_scope')) {
            $loader->setScope(spl_object_hash($loader)); // unique scope for each new loader instance
        } else {
            $loader->setScope($settings->get('cache_scope', FilesystemLoader::SCOPE_DEFAULT));
        }

        // adds an @namespaces to templates to allow twig templates to use embed/include statements that reuse
        // existing templates to override blocks instead of copying whole templates (from different locations)
        // usage example: {% include "@Honeybee/foo.twig" ignore if missing %}
        $loader->addPath(AgaviConfig::get('core.template_dir'), 'App');
        $loader->addPath(AgaviConfig::get('core.honeybee_template_dir'), 'Honeybee');

        foreach ($this->getModuleTemplatesPaths() as $module_name => $templates_path) {
            $loader->addPath($templates_path, $module_name);
        }

        return $loader;
    }

    protected function getModuleTemplatesPaths()
    {
        $paths = [];

        $directory_iterator = new DirectoryIterator(AgaviConfig::get('core.module_dir'));
        foreach ($directory_iterator as $module_directory) {
            if ($module_directory->isDot() || !$module_directory->isDir()) {
                continue;
            }

            $templates_path = $module_directory->getPathname() . DIRECTORY_SEPARATOR . 'templates';
            if (is_readable($templates_path)) {
                $paths[$module_directory->getFilename()] = $templates_path;
            }
        }

        return $paths;
    }
}
