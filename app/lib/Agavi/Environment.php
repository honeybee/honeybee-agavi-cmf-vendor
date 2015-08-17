<?php

namespace Honeybee\FrameworkBinding\Agavi;

use Honeybee\Common\Error\RuntimeError;

/**
 * Local environment information for the application. The local configuration
 * is provided via environaut and a etc/local/config.php file.
 */
class Environment
{
    /**
     * The name of 'php_command' environment setting (a path to the php executable to use)
     */
    const CFG_PHP_COMMAND = 'php_command';

    /**
     * The name of 'environment' config setting.
     */
    const CFG_ENVIRONMENT = 'agavi_environment';

    /**
     * The name of our base-href setting.
     */
    const CFG_BASE_HREF = 'base_href';

    /**
     * @var Environment instance of this class
     */
    private static $instance;

    /**
     * @var array data from the current local config file
     */
    private $config = array();

    /**
     * @var string suffix used for the current environment name
     */
    private $environment_modifier = '';

    /**
     * @var string path to the application root directory
     */
    private $application_dir = '';

    /**
     * Create a new Environment instance.
     *
     * @param string $environment_modifier string to append to environment name
     */
    private function __construct($environment_modifier = '')
    {
        $this->application_dir = getenv('APPLICATION_DIR');

        if ($this->application_dir === false) {
            throw new RuntimeError('APPLICATION_DIR not set. Environment not initialized.');
        }

        $local_config = $this->application_dir . str_replace('/', DIRECTORY_SEPARATOR, '/etc/local/config.php');

        $this->config = include($local_config);
        $environment = getenv('AGAVI_ENVIRONMENT');
        if ($environment !== false) {
            $this->config[self::CFG_ENVIRONMENT] = $environment;
        }

        if (!empty($environment_modifier)) {
            $this->config[self::CFG_ENVIRONMENT] .= $environment_modifier;
            $this->environment_modifier = $environment_modifier;
        }
    }

    /**
     * Initialize our config instance by loading our local evironment settings.
     *
     * AGAVI_ENVIRONMENT environment variable may be used to override the
     * environment string set in the local config file.
     *
     * APPLICATION_DIR environment variable will be used to load the local
     * config file.
     *
     * @param string $environment_modifier string to append to environment name
     *
     * @return Environment instance with local settings loaded already
     */
    public static function load($environment_modifier = '')
    {
        if (null === self::$instance) {
            self::$instance = new Environment($environment_modifier);
        }

        return self::$instance;
    }

    /**
     * Returns the path to the current application's root directory.
     *
     * @return string full path to application
     */
    public static function getApplicationDir()
    {
        return self::$instance->application_dir;
    }

    /**
     * Returns the name of the current environment (e.g. development-vagrant).
     *
     * @return string complete name of current environment
     */
    public static function toEnvString()
    {
        return self::getEnvironment();
    }

    /**
     * Return the current environment.
     *
     * @return string
     */
    public static function getEnvironment()
    {
        return self::$instance->config[self::CFG_ENVIRONMENT];
    }

    /**
     * Return the current environment name's suffix used as a modifier.
     *
     * @return string suffix of current environment name that is used as a modifier
     */
    public static function getEnvironmentModifier()
    {
        return self::$instance->environment_modifier;
    }

    /**
     * Return the current environment (without the environment modifier suffix).
     *
     * @return string name of current environment without the modifier suffix
     */
    public static function getCleanEnvironment()
    {
        $environment = self::getEnvironment();
        $modifier = self::getEnvironmentModifier();

        return str_replace($modifier, '', $environment);
    }

    /**
     * Return the path to the php binary to use for the current environment.
     *
     * @return string path to php executable
     */
    public static function getPhpPath()
    {
        return self::$instance->config[self::CFG_PHP_COMMAND];
    }

    /**
     * Return the base path (URL) of the current environment (e.g. 'https://example.com/').
     *
     * @return string full URL to the current application
     */
    public static function getBaseHref()
    {
        return self::$instance->config[self::CFG_BASE_HREF];
    }

    /**
     * @return boolean flag whether or not testing is enabled in the current environment.
     */
    public static function isTestingEnabled()
    {
        return (bool)self::$instance->config['testing_enabled'];
    }
}
