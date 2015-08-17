<?php

namespace Honeybee\FrameworkBinding\Agavi\Filter;

use Honeybee\FrameworkBinding\Agavi\Logging\Logger;
use Honeybee\Common\Util\StringToolkit;
use AgaviConfig;
use AgaviContext;
use AgaviExecutionContainer;
use AgaviFilter;
use AgaviFilterChain;
use AgaviIGlobalFilter;

/**
 * This global filter adds stylesheets and scripts to the (html) response.
 *
 * The static "addModule" is usually called by the customExecutionFilter.
 * Adding files can also be done manually anywhere to add other modules'
 * files to the response.
 */
class ModuleResourcesResponseFilter extends AgaviFilter implements AgaviIGlobalFilter
{
    /**
     * List of modules that have been used in the current request (grouped by output_type).
     */
    protected static $modules = array();

    protected $current_output_type;

    protected $supported_output_types = array();

    /**
     * Adds the given module name for the given output type to the internal list of modules for that output type that
     * will be used to generate module specific stylesheet and script tags in the response. This is mainly useful for
     * HTML output types.
     *
     * @param string $module_name name of module
     * @param string $output_type name of output type
     *
     * @return void
     */
    public static function addModule($module_name, $output_type)
    {
        if (isset(static::$modules[$output_type]) && in_array($module_name, static::$modules[$output_type])) {
            return;
        }

        static::$modules[$output_type][] = $module_name;
    }

    /**
     * @param \AgaviContext $context
     * @param array $parameters parameters from the global_config.xml file
     */
    public function initialize(AgaviContext $context, array $parameters = array())
    {
        parent::initialize($context, $parameters);

        $this->supported_output_types = $this->getParameter('output_types', array());
    }

    /**
     * Replace placeholders to add the styles and scripts for all executed html views to the response.
     *
     * @param AgaviFilterChain A FilterChain instance.
     * @param AgaviExecutionContainer The current execution container.
     */
    public function execute(AgaviFilterChain $filter_chain, AgaviExecutionContainer $container)
    {
        $filter_chain->execute($container);
        $response = $container->getResponse();

        $output = null;
        if (!$response->isContentMutable() || !($output = $response->getContent())) {
            return false;
        }

        $this->current_output_type = $response->getOutputType()->getName();
        if (!in_array($this->current_output_type, $this->supported_output_types)) {
            return false;
        }

        $search = array('<!-- %%STYLESHEETS%% -->', '<!-- %%REQUIREJS%% -->');
        $replace = array($this->getStyleTags(), $this->getRequiredScripts());

        $output = str_replace($search, $replace, $output);

        $response->setContent($output);
    }

    /**
     * Returns a stylesheet link for the currently active theme and each styles.css file of each module that was used
     * in the current request.
     *
     * @return string with link tags to stylesheet files
     */
    protected function getStyleTags()
    {
        $tags = '';

        $current_theme = AgaviConfig::get('themes.default', 'honeybee');
        $theme_filename = ResourceCompiler::THEME_MAIN_CSS_FILE;
        $theme_path = 'static/themes/' . $current_theme . '/' . $theme_filename;

        if (is_readable($theme_path)) {
            $tags .= sprintf(
                '<link rel="stylesheet" type="text/css" href="%s" />' . PHP_EOL,
                StringToolkit::escapeHtml($theme_path . '?cb=' . filemtime($theme_path))
            );
        } else {
            // TODO define callback to be called when configured/default honeybee theme is not available?
            $lm = $this->getContext()->getLoggerManager();
            $lm->logTo(
                $lm->getDefaultLoggerName(),
                Logger::CRITICAL,
                __METHOD__,
                'File of theme "' . $current_theme . '" not readable: ' . $theme_path
            );
        }

        $modules_path_prefix = "static/modules";
        if (AgaviConfig::get('requirejs.use_optimized', false)) {
            $modules_path_prefix = "static/modules-built";
        }

        if (isset(static::$modules[$this->current_output_type])) {
            foreach (static::$modules[$this->current_output_type] as $module) {
                $file = $modules_path_prefix . '/' . $module . '/' . ResourceCompiler::MODULE_MAIN_CSS_FILE;
                if (is_readable($file)) {
                    $tags .= sprintf(
                        '<link rel="stylesheet" type="text/css" href="%s" />' . PHP_EOL,
                        StringToolkit::escapeHtml($file . '?cb=' . filemtime($file))
                    );
                }
            }
        }

        return $tags;
    }

    /**
     * Returns a script tag that requires the Widget js base class that requires jquery and JSB.
     *
     * @return string with a script tag containing requirejs calls to have widget support
     */
    protected function getRequiredScripts()
    {
        $requires = '';
        if (AgaviConfig::get('requirejs.use_optimized', true) && isset(static::$modules[$this->current_output_type])) {
            /*foreach (static::$modules[$this->current_output_type] as $module) {
                $file = 'static/modules/' . $module . '/Main.js';
                if (is_readable($file)) {
                    $requires .= 'require(["' . $module . '/Main"]);';
                }
            }*/
            $requires = 'require(["Honeybee_Core/AllModules"]);';
        } else {
            $requires = 'require(["Honeybee_Core/Widget"]);';
        }

        $output = '';
        if (!empty($requires)) {
            $output = '<script type="text/javascript">' . $requires . '</script>';
        }

        return $output;
    }
}
