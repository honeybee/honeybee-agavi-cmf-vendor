<?php

use Honeybee\FrameworkBinding\Agavi\Environment;

// get application directory
$application_dir = getenv('APPLICATION_DIR');
if ($application_dir === false) {
    throw new \Exception('APPLICATION_DIR not set. Aborting.');
}

// setup Agavi and AgaviConfig
$root_dir = dirname(dirname(__FILE__));
require($root_dir . str_replace('/', DIRECTORY_SEPARATOR, '/app/config.php'));

// register dat0r and composer autoloads
$autoloads_include = $application_dir . str_replace('/', DIRECTORY_SEPARATOR, '/app/config/includes/autoload.php');
if (is_readable($autoloads_include)) {
    require($autoloads_include);
}

// make generated files group writeable for easy switch between web/console
umask(02);

// by default the environment name is not modified via a suffix
if (!isset($environment_modifier)) {
    $environment_modifier = '';
}

// when no environment modifier was fixed, try to determine whether this
// request might be a stateless API request with e.g. basic auth access
if (empty($environment_modifier) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $environment_modifier = '-stateless-api';
}

// load local config and make it accessible via a static class
Environment::load($environment_modifier);
AgaviConfig::set('core.clean_environment', Environment::getCleanEnvironment());

AgaviConfig::set('core.base_href', Environment::getBaseHref());

// allow a custom cache directory location
$cache_dir = getenv('APPLICATION_CACHE_DIR');
//$cache_dir = '/dev/shm/cache';
if ($cache_dir === false) {
    // default cache directory takes environment into account to mitigate cases
    // where the environment on a server is switched and the cache isn't cleared
    AgaviConfig::set(
        'core.cache_dir',
        AgaviConfig::get('core.app_dir')
        . DIRECTORY_SEPARATOR . 'cache'
        . DIRECTORY_SEPARATOR
        . AgaviConfig::get('core.environment'),
        true, // overwrite
        true // readonly
    );
    AgaviConfig::set(
        'core.cache_dir_without_env',
        AgaviConfig::get('core.app_dir') . DIRECTORY_SEPARATOR . 'cache',
        true,
        true
    );
} else {
    // use cache directory given by environment variable
    $cache_dir = realpath($cache_dir);
    AgaviConfig::set('core.cache_dir', $cache_dir, true, true); // overwrite, readonly
    AgaviConfig::set('core.cache_dir_without_env', $cache_dir, true, true);
}

// bootstrap the framework/application using an environment name
//$_SERVER['AGAVI_ENVIRONMENT'] = Environment::toEnvString();
Agavi::bootstrap(Environment::toEnvString());

if (!isset($default_context)) {
    $default_context = getenv('AGAVI_CONTEXT');
}

if (!$default_context) {
    throw new RuntimeException("Missing default context setting.");
}
// this is one of the most important settings for agavi
// contexts are e.g. 'web', 'console', 'soap' or 'xmlrpc'
AgaviConfig::set('core.default_context', $default_context);

if (preg_match('/.*development.*/', Environment::getCleanEnvironment())) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL ^ E_STRICT);
}
