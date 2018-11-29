<?php

namespace Honeygavi\Renderer;

use AgaviConfig;
use AgaviFileTemplateLayer;
use AgaviTemplateLayer;
use AgaviToolkit;
use AgaviTwigRenderer;

use Twig_Environment;
use Twig_Loader_Array;
use Twig_Template;

use MtHaml\Environment as MtHamlEnvironment;
use MtHaml\Support\Twig\Extension as MtHamlTwigExtension;
use MtHaml\Filter\Markdown\MichelfMarkdown as MtHamlMarkdownFilter;
use Honeygavi\Template\Twig\Loader\MtHamlTwigLoader;

use Michelf\MarkdownExtra;

use Honeygavi\Filter\AssetCompiler;
use Honeygavi\Template\Twig\Loader\FilesystemLoader;

//use Honeygavi\Logging;

/**
 * Extends the AgaviTwigRenderer to add twig extensions via parameters. If you
 * need more functionality you should extend the AgaviTwigRenderer by yourself
 * and use that in the output_types.xml file. This renderer registers the template_dirs
 * from the output types and registers twig template namespaces for each module and
 * the app/templates and honeybee/app/templates directories.
 */
class TwigRenderer extends AgaviTwigRenderer
{
    /**
     * @var array of module name => module path
     */
    protected $modules;

    /**
     * @var MtHaml\Environment MtHamlEnvironment for Twig
     */
    protected $mthaml;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = array())
    {
        parent::__construct($parameters);

        $this->modules = array();

        foreach (AssetCompiler::getAvailableModuleDirectories() as $module_path) {
            $this->modules[basename($module_path)] = $module_path;
        }
    }

    /**
     * Render the presentation and return the result. This expands
     * the default behaviour of the original Agavi method by
     * discarding non-existant paths silently (as Twig doesn't
     * like non-existing directories).
     *
     * Lookup is as follows:
     *
     * 1. paths from 'template_dirs' parameter (or 'core.template_dir)
     * 2. path to the directory the current template is in
     * 3. path to the module's template directory ('agavi.template.directory' parameter from the module's module.xml)
     *
     * @param AgaviTemplateLayer $layer template layer to render
     * @param array $attributes template variables
     * @param array $slots slots
     * @param array $moreAssigns associative array of additional assigns
     *
     * @return string rendered result
     */
    public function render(
        AgaviTemplateLayer $layer,
        array &$attributes = array(),
        array &$slots = array(),
        array &$moreAssigns = array()
    ) {
        $twig_template = $this->loadTemplate($layer);

        $data = array();

        // template vars
        if ($this->extractVars) {
            foreach ($attributes as $name => $value) {
                $data[$name] = $value;
            }
        } else {
            $data[$this->varName] = $attributes;
        }

        // slots
        $data[$this->slotsVarName] = $slots;

        // dynamic assigns (global ones were set in getEngine())
        $finalMoreAssigns = self::buildMoreAssigns($moreAssigns, $this->moreAssignNames);
        foreach ($finalMoreAssigns as $key => $value) {
            $data[$key] = $value;
        }

        return $twig_template->render($data);
    }

    /**
     * @param AgaviTemplateLayer $layer
     *
     * @return Twig_Template
     */
    public function loadTemplate(AgaviTemplateLayer $layer)
    {
        $source = $this->getSource($layer);

        $twig = $this->getEngine();

        if ($this->getParameter('use_haml', false)) {
            $new_loader = new MtHamlTwigLoader(
                $this->getMtHaml(),
                $twig->getLoader()
            );
            $twig->setLoader($new_loader);
        }

        return $twig->loadTemplate($source);
    }

    /**
     * Return an initialized Twig instance.
     *
     * @return Twig_Environment
     */
    protected function getEngine()
    {
        $twig = parent::getEngine();

        foreach ($this->getParameter('extensions', array()) as $extension_class_name) {
            $ext = $this->getContext()->getServiceLocator()->make($extension_class_name);

            // as the renderer is reusable it may have the extension already
            if (!$twig->hasExtension($extension_class_name)) {
                $twig->addExtension($ext);
            }

            if ($this->getParameter('use_haml', false)) {
                $ext = new MtHamlTwigExtension($this->getMtHaml());
                if (!$twig->hasExtension(MtHamlTwigExtension::class)) {
                    $twig->addExtension($ext);
                }
            }
        }

        return $twig;
    }

    protected function getSource(AgaviTemplateLayer $layer)
    {
        $twig = $this->getEngine();

        $template_dirs = (array) $this->getParameter('template_dirs', array(AgaviConfig::get('core.template_dir')));
        $path = $layer->getResourceStreamIdentifier();

        if ($layer instanceof AgaviFileTemplateLayer) {
            $paths = array();
            // allow loading from the main project template dir by default
            // (and any other directories the user has set through configuration)
            foreach ($template_dirs as $dir) {
                // replace e.g. {module} with name of the current module if possible
                $dir = AgaviToolkit::expandVariables(
                    $dir,
                    array_merge(
                        array_filter($layer->getParameters(), 'is_scalar'),
                        array_filter($layer->getParameters(), 'is_null')
                    )
                );
                if (is_dir($dir) && is_readable($dir)) {
                    $paths[] = $dir;
                } else {
                    //\AgaviContext::getInstance()->getLoggerManager()->logTo(
                    //null, Logging\Logger::INFO, __METHOD__, "Template directory $dir does not exist or is not
                    //readable. Check 'core.template_dir' setting or the TwigRenderer's 'template_dirs' parameter
                    //or create the directory.");
                }
            }

            // set the directory the template is in as the first path to load from, and the directory set on
            // the layer second - that way, including another template inside this template will look at e.g.
            // a locale subdirectory first before falling back to the originally defined folder
            $pathinfo = pathinfo($path);
            $paths[] = $pathinfo['dirname'];
            $paths[] = $layer->getParameter('directory');

            $loader = new FilesystemLoader($paths);
            $loader->setScope('views');
            $loader->setAllowedExtensions(
                $this->getParameter('allowed_template_extensions', array('.html', '.twig', '.haml'))
            );

            // adds namespaces to templates to allow twig templates to use embed/include statements that reuse
            // existing templates from specific locations (determined by their namespace prefix, e. g. @App )
            $loader->addPath(AgaviConfig::get('core.template_dir'), 'App');
            $loader->addPath(AgaviConfig::get('core.honeybee_template_dir'), 'Honeybee');

            foreach ($this->modules as $module_name => $module_path) {
                $loader->addPath($module_path, $module_name);
            }

            $twig->setLoader($loader);
            $source = $pathinfo['basename'];
        } else {
            // a stream template or whatever; either way, it's something Twig can't load directly :S
            $source = file_get_contents($path);
            $name = sprintf('__some_string_template__%s', hash('sha256', $source, false));
            $loader = new Twig_Loader_Array([
                $name => $source,
            ]);
            $twig->setLoader($loader);
        }

        return $source;
    }

    protected function getMtHaml()
    {
        if (!$this->mthaml) {
            $markdown_parser = new MarkdownExtra();
            $markdown_filter = new MtHamlMarkdownFilter($markdown_parser);
            $this->mthaml = new MtHamlEnvironment(
                'twig',
                $this->getParameter(
                    'haml_options',
                    array(
                        'enable_escaper' => false,
                        'enable_dynamic_attrs' => false,
                        'autoclose' => array(
                            'area', 'base', 'br', 'col', 'embed', 'hr',
                            'img', 'input', 'keygen', 'link', 'meta',
                            'param', 'source', 'track', 'wbr'
                        )
                        // html5 void elements - see http://www.w3.org/TR/html5/syntax.html#void-elements
                    )
                ),
                array(
                    'markdown' => $markdown_filter
                )
            );
        }

        return $this->mthaml;
    }
}
