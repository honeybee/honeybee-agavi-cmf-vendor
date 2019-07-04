<?php

namespace Honeygavi\Filter;

use AgaviConfig;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

/**
 * The AssetPacker symlinks and compiles themes, compiles scss files and optimizes javascript files via r.js.
 *
 * @TODO Refactor these tasks into more general classes and move it out of FrameworkBinding via construcor config?
 */
class AssetCompiler
{
    const THEME_MAIN_FILE = 'main';
    const THEME_MAIN_SCSS_FILE = 'main.scss';
    const THEME_MAIN_CSS_FILE = 'main.css';

    const MODULE_MAIN_FILE = 'styles';
    const MODULE_MAIN_SCSS_FILE = 'styles.scss';
    const MODULE_MAIN_CSS_FILE = 'styles.css';

    public static $module_dirs = null;

    /**
     * Symlinks all "assets" folders of all modules to the "pub/static/modules/[module_name]" folders to have the
     * module specific styles, scripts, static templates and binaries available for scss compilation and requirejs.
     *
     * Special folders next to the module assets links in "pub/static" are:
     * - themes (for all builtin and project custom themes)
     *
     * @throws \RuntimeException in case of symlinking errors
     */
    public function symlinkModuleAssets()
    {
        $old_cwd = getcwd();

        $cms_dir = AgaviConfig::get('core.cms_dir');

        $target_location = $this->getPubStaticModuleAssetsFolder();
        if (!chdir($target_location)) {
            throw new RuntimeException('Could not change directory to target location: ' . $target_location);
        }

        foreach (self::getAvailableModuleDirectories() as $module_directory) {
            $module_name = basename($module_directory);
            $target_module_name = $module_name;

            // check for existing symlinks or symlink the module's assets folder
            if (is_readable($target_module_name)) {
                if (is_link($target_module_name)) {
                    $link_target_path = realpath(readlink($target_module_name));
                    if (mb_strpos($link_target_path, $cms_dir) !== 0) { // symlink target must start with cms_dir
                        throw new RuntimeException(
                            'Linked module "' . $module_name . '" does not ' .
                            'point to a directory inside the cms directory!'
                        );
                    }
                } else {
                    throw new RuntimeException(
                        'Assets folder of module "' . $module_name . '" could not be symlinked into pub/static ' .
                        'folder as a file with name "' . $target_module_name . '" exists in: ' . getcwd()
                    );
                }
            } else {
                $assets_dir = '../../../app/modules/' . $module_name . '/assets';
                if (is_readable($assets_dir)) {
                    if (!symlink($assets_dir, $target_module_name)) {
                        chdir($old_cwd);
                        throw new RuntimeException(
                            'The symlinking of "' . $module_name . '/assets" to ' .
                            '"pub/static/' . $target_module_name . '" failed!'
                        );
                    }
                } else {
                    // module does not seem to have a assets folder, so no symlink necessary
                }
            }
        }

        chdir($old_cwd);
    }

    /**
     * Compiles all themes in "pub/static/themes" via SASS with the given style.
     *
     * @param string $style SASS style value (nested|expanded|compact|compressed)
     * @param array $report report will contain one entry for each directory/theme compiled via sass
     *
     * @return boolean true when all themes were compiled successfully, false when there was at least one erroneous one.
     */
    public function compileThemes($style = 'compressed', array &$report = array())
    {
        $success = true;
        $themes_directories = $this->getAvailableThemeDirectories(array($this->getPubStaticThemesFolder()));
        foreach ($themes_directories as $theme_directory) {
            $success &= $this->compileTheme(basename($theme_directory), $style, $report);
        }

        return $success;
    }

    /**
     * Compiles the given theme via SASS with the given style.
     *
     * @param string $theme_name name of theme to compile
     * @param string $style SASS style value (nested|expanded|compact|compressed)
     * @param array $report report will contain one entry for each directory/theme compiled via sass
     *
     * @return boolean true when the theme was compiled successfully, false when there was an error.
     *
     * @throws \RuntimeException when a theme folder is not readable
     */
    public function compileTheme($theme_name, $style = 'compressed', array &$report = array())
    {
        if (empty($theme_name)) {
            throw new InvalidArgumentException('No theme name specified for compilation.');
        }

        $success = true;

        $pub_themes_dir = $this->getPubStaticThemesFolder();
        $theme_directory = $pub_themes_dir . DIRECTORY_SEPARATOR . $theme_name;

        if (!is_readable($theme_directory)) {
            throw new RuntimeException('Theme "' . $theme_name . '" is not readable: ' . $theme_directory);
        }

        $input_file = $theme_directory . DIRECTORY_SEPARATOR . self::THEME_MAIN_SCSS_FILE;
        if (!is_readable($input_file)) {
            throw new RuntimeException('Theme "' . $theme_name . '" has no readable input file: ' . $input_file);
        }

        $output_file = $theme_directory . DIRECTORY_SEPARATOR . self::THEME_MAIN_CSS_FILE;

        $cmd = $this->getScssCommand($input_file, $output_file, $style);

        $report[$theme_directory] = self::runCommand($cmd, $theme_directory);
        $report[$theme_directory]['name'] = 'Theme ' . basename($theme_directory);

        if (!$report[$theme_directory]['success']) {
            $success = false;
        }

        return $success;
    }

    /**
     * Compiles all "style.scss" files in modules that were linked into the "pub/static" folder.
     *
     * @param string $style SASS style value (nested|expanded|compact|compressed)
     * @param array $report report will contain one entry for each directory/theme compiled via sass
     *
     * @return boolean true when all files were compiled successfully, false when there was at least one erroneous one.
     */
    public function compileModuleStyles($style = 'compressed', array &$report = array())
    {
        $success = true;

        $module_directories = self::getAvailableModuleDirectories();

        foreach ($module_directories as $module_directory) {
            $module_name = basename($module_directory);
            $directory = $this->getPubStaticModuleAssetsFolder() . DIRECTORY_SEPARATOR . $module_name;

            $input_file = $directory . DIRECTORY_SEPARATOR . self::MODULE_MAIN_SCSS_FILE;
            if (!is_readable($input_file)) {
                continue;
            }

            $output_file =  $directory . DIRECTORY_SEPARATOR . self::MODULE_MAIN_CSS_FILE;

            $cmd = $this->getScssCommand($input_file, $output_file, $style);

            $report[$module_directory] = self::runCommand($cmd, $module_directory);
            $report[$module_directory]['name'] = 'Module ' . $module_name;

            if (!$report[$module_directory]['success']) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Watches the given theme via SASS with the given style.
     *
     * @param string $theme_name name of theme to compile
     * @param string $style SASS style value (nested|expanded|compact|compressed)
     *
     * @return Process
     *
     * @throws \RuntimeException when a theme folder is not readable
     */
    public function getWatchThemeProcess($theme_name, $style = 'compressed')
    {
        if (empty($theme_name)) {
            throw new InvalidArgumentException('No theme name specified for compilation.');
        }

        $pub_themes_dir = $this->getPubStaticThemesFolder();
        $theme_directory = $pub_themes_dir . DIRECTORY_SEPARATOR . $theme_name;

        if (!is_readable($theme_directory)) {
            throw new RuntimeException('Theme "' . $theme_name . '" is not readable: ' . $theme_directory);
        }

        $input_file = $theme_directory . DIRECTORY_SEPARATOR . self::THEME_MAIN_SCSS_FILE;
        if (!is_readable($input_file)) {
            throw new RuntimeException('Theme "' . $theme_name  . '" has no readable input file: ' . $input_file);
        }

        $output_file = $theme_directory . DIRECTORY_SEPARATOR . self::THEME_MAIN_CSS_FILE;

        $cmd = $this->getScssWatchCommand($input_file, $output_file, $style);

        $process = new Process($cmd);

        return $process;
    }

    /**
     * Returns an array of Process instances that may be used to watch the styles.scss files of honeybee modules
     * for changes.
     *
     * @param string $style sass compilation style (compressed, nested etc.)
     *
     * @return array<\Symfony\Component\Process\Process> or empty array
     */
    public function getWatchModuleStylesProcesses($style = 'compressed')
    {
        $processes = array();

        $agavi_modules = self::getAvailableModuleDirectories();
        foreach ($agavi_modules as $module_directory) {
            $module_name = basename($module_directory);
            $module_directory = $this->getPubStaticModuleAssetsFolder();
            $input_file = $module_directory . DIRECTORY_SEPARATOR
                . $module_name . DIRECTORY_SEPARATOR . self::MODULE_MAIN_SCSS_FILE;

            if (!is_readable($input_file)) {
                continue;
            }

            $output_file = $module_directory . DIRECTORY_SEPARATOR
                . $module_name . DIRECTORY_SEPARATOR . self::MODULE_MAIN_CSS_FILE;

            $cmd = $this->getScssWatchCommand($input_file, $output_file, $style);

            $processes["Module $module_name"] = new Process($cmd);
        }

        return $processes;
    }

    /**
     * Returns a shell command that may be executed to compile the input scss file into the output css file.
     *
     * @param string $input_file full path to .scss file
     * @param string $output_file full path to .css file
     * @param string $style sass compilation style, e.g. "compressed" or "nested"
     *
     * @return string sass scss compilation command
     */
    public function getScssCommand($input_file, $output_file, $style = 'compressed')
    {
        /*
         * RUBYOPT="" LANG=en_US.UTF-8
         * '/opt/ruby/bin/sass' --scss --style nested --no-cache --unix-newlines --precision 3
         * '/some/input_file.scss' 'some_output_file.css'
         */
        $command = sprintf(
            str_replace(
                [ '#1', '#2', '#3', '#4', '#5', '#6'],
                [ '%1$s', '%2$s', '%3$s', '%4$s', '%5$s', '%6$s'],
                AgaviConfig::get('sass.cmd_tpl', '#1 #2 --scss #3 #4 #5 #6 -E "UTF-8"')
            ),
            AgaviConfig::get('sass.env', 'RUBYOPT="" LC_ALL="en_US.UTF-8" LANG="en_US.UTF-8"'),
            ProcessUtils::escapeArgument(AgaviConfig::get('sass.cmd', '/usr/local/bin/sass')),
            ProcessUtils::escapeArgument('--style=' . $style),
            $this->getSassCliOptions(),
            ProcessUtils::escapeArgument($input_file),
            ProcessUtils::escapeArgument($output_file)
        );

        return $command;
    }

    /**
     * Returns a shell command that may be executed to watch the input scss file for changes to create the output file.
     *
     * @param string $input_file full path to .scss file
     * @param string $output_file full path to .css file
     * @param string $style sass compilation style, e.g. "compressed" or "nested"
     *
     * @return string sass scss watch command
     */
    public function getScssWatchCommand($input_file, $output_file, $style = 'compressed')
    {
        /*
         * RUBYOPT="" LANG=en_US.UTF-8
         * '/opt/ruby/bin/sass' --watch --scss --style nested --no-cache --unix-newlines --precision 3
         * '/some/input_file.scss':'some_output_file.css'
         */
        $command = sprintf(
            str_replace(
                [ '#1', '#2', '#3', '#4', '#5', '#6'],
                [ '%1$s', '%2$s', '%3$s', '%4$s', '%5$s', '%6$s'],
                AgaviConfig::get('sass.watch_cmd_tpl', '#1 #2 --watch #3 #4 #5:#6')
            ),
            AgaviConfig::get('sass.env', 'RUBYOPT="" LANG=en_US.UTF-8'),
            ProcessUtils::escapeArgument(AgaviConfig::get('sass.cmd', '/usr/local/bin/sass')),
            ProcessUtils::escapeArgument('--style=' . $style),
            $this->getSassCliOptions("--trace"),
            ProcessUtils::escapeArgument($input_file),
            ProcessUtils::escapeArgument($output_file)
        );

        return $command;
    }

    /**
     * Runs RJS on the "pub/static/modules" folder to compile all assets according to the given buildfile.
     *
     * @param string $buildfile full path default build configuration file used for assets folder of modules
     * @param string $style requirejs optimize style (uglify|uglify2|none) - the "closure" style for Google's Closure
     *              Compiler is not available without java.
     * @param array $report report will contain one entry for each directory/theme compiled via sass
     *
     * @return boolean true when all themes were compiled successfully, false when there was at least one erroneous one.
     */
    public function compileJs($buildfile = 'buildconfig.js', $style = 'uglify2', array &$report = array())
    {
        if (!is_readable($buildfile)) {
            throw new InvalidArgumentException('Given r.js configuration file is not readable: ' . $buildfile);
        }

        $success = true;

        $name = basename($buildfile);

        $rjs_compile_command = sprintf(
            '%s -o %s %s',
            ProcessUtils::escapeArgument(
                AgaviConfig::get('requirejs.cmd_rjs', 'vendor/node_modules/honeybee/node_modules/.bin/r.js')
            ),
            ProcessUtils::escapeArgument($buildfile),
            ProcessUtils::escapeArgument('optimize=' . $style)
        );

        $report[$name] = self::runCommand($rjs_compile_command, AgaviConfig::get('core.cms_dir'));
        $report[$name]['name'] = $name;

        if (!$report[$name]['success']) {
            $success = false;
        }

        return $success;
    }

    /**
     * Returns an array of filesystem paths for all currently linked modules in app/modules.
     *
     * @return array of module paths (absolute filesystem paths)
     */
    public static function getAvailableModuleDirectories()
    {
        if (null === self::$module_dirs) {
            $found = glob(AgaviConfig::get('core.module_dir') . '/*', GLOB_ONLYDIR);
            if (false === $found) {
                $found = array();
            }

            self::$module_dirs = array_merge(array(), $found);
        }

        return self::$module_dirs;
    }

    /**
     * Runs the given command in the given working directory.
     *
     * @param string $command command to execute (please escape arguments via e.g. using ProcessUtils::escapeArgument())
     * @param string $working_directory
     *
     * @return array with "exitcode", "exitcode_text", "stdout", "stderr" and "success" flag
     *
     * @throws \InvalidArgumentException when working directory is not readable
     * @throws \Symfony\Component\Process\Exception\RuntimeException on errors executing the command
     */
    public static function runCommand($command, $working_directory)
    {
        if (!is_readable($working_directory)) {
            throw new InvalidArgumentException('Given working directory is not readable: ' . $working_directory);
        }

        $result = array(
            'cmd' => $command,
            'stdout' => '',
            'stderr' => '',
            'success' => false
        );

        $process = new Process($command, $working_directory);
        $process->setTimeout(300);
        $process->run();

        $result['exitcode'] = $process->getExitCode();
        $result['exitcode_text'] = $process->getExitCodeText();
        $result['stdout'] = $process->getOutput();
        $result['stderr'] = $process->getErrorOutput();
        $result['success'] = $process->isSuccessful();

        return $result;
    }

    /**
     * @return string full path to pub/static/themes folder
     */
    public static function getPubStaticThemesFolder()
    {
        return self::getPubStaticFolder() . DIRECTORY_SEPARATOR . 'themes';
    }

    /**
     * @return string full path to pub/static/modules folder
     */
    public static function getPubStaticModuleAssetsFolder()
    {
        return self::getPubStaticFolder() . DIRECTORY_SEPARATOR . 'modules';
    }

    /**
     * @return string full path to pub/static folder
     */
    public static function getPubStaticFolder()
    {
        return realpath(AgaviConfig::get('core.pub_dir') . DIRECTORY_SEPARATOR . 'static');
    }

    /**
     * @param string $additional_arguments additional CLI arguments to use (like --trace); please escape the arguments!
     *
     * @return string default CLI options for SASS commands
     */
    protected function getSassCliOptions($additional_arguments = '')
    {
        $sass_options = AgaviConfig::get('sass.cli_options', "'--no-cache' '--unix-newlines' '--precision=3'");

        if (true === AgaviConfig::get('sass.debug', false)) {
            $sass_options .= " '--debug-info' '--line-numbers' '--line-comments' "; // '--trace'
        }

        if (!empty($additional_arguments)) {
            $sass_options .= ' ' . $additional_arguments . ' ';
        }

        return $sass_options;
    }

    /**
     * @param array $theme_locations folders to search themes in
     *
     * @return array of found themes in those directories
     */
    protected function getAvailableThemeDirectories(array $theme_locations = array())
    {
        $themes = array();

        foreach ($theme_locations as $theme_location) {
            if (!is_readable($theme_location)) {
                continue;
            }

            $found = glob($theme_location . '/*', GLOB_ONLYDIR);
            if (false === $found) {
                $found = array();
            }

            $themes = array_merge($themes, $found);
        }

        return $themes;
    }
}
