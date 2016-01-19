<?php

// make generated files group writable for easy switch between web/console; not thread safe though
// @todo remove this and let provisioning use "chmod +a" or "setfacl" instead
// @see http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
umask(0002); // permissions will be 0775, 0000 => 0777

// application dir is either set via env variable or will be ./../
$application_dir = @$application_dir ?: getenv('APP_DIR');
if (empty($application_dir) || realpath($application_dir) === false || !is_readable($application_dir)) {
    $application_dir = realpath(__DIR__ . '/../');
    if (empty($application_dir) || !putenv('APP_DIR=' . $application_dir)) {
        throw new Exception('APP_DIR not set');
    }
}

// local configuration folder contains files with e.g. sensitive (uncommitted) data put there via provisioning
$local_config_dir = @$local_config_dir ?: getenv('APP_LOCAL_CONFIG_DIR');
if (empty($local_config_dir) || realpath($local_config_dir) === false || !is_readable($local_config_dir)) {
    // convention here: the application_dir is thought to be a FQDN like "myapp.local"
    $local_config_dir = realpath('/usr/local/' . basename($application_dir));
    if (empty($local_config_dir) || !is_readable($local_config_dir)) {
        throw new Exception(
            'Local configuration directory non-existant or not readable: ' . '/usr/local/' . basename($application_dir)
        );
    }
    if (!putenv('APP_LOCAL_CONFIG_DIR=' . $local_config_dir)) {
        throw new Exception('Local configuration directory could not be set via putenv.');
    }
}

// determine environment name to use
$environment = @$environment ?: getenv('APP_ENV');
if (empty($environment)) {
    $environment_file = $local_config_dir . '/environment';
    // try to read environment name from a file in the application's local configuration directory
    if (!is_readable($environment_file)) {
        throw new Exception(
            'Environment name not specified via APP_ENV or available in file: ' . $environment_file
        );
    }
    $environment = trim(file_get_contents($environment_file));
    if (empty($environment)) {
        throw new Exception('Empty environment name read from file: ' . $environment_file);
    }
}

// store application environment name
if (!putenv('APP_ENV=' . $environment)) {
    throw new Exception('Could not set APP_ENV environment variable.');
}

// environment modifier (suffix)
$environment_modifier = @$environment_modifier ?: getenv('APP_ENV_MODIFIER');
if (empty($environment_modifier)) {
    $environment_modifier = '';
}

// load regular composer autoload
require($application_dir . '/vendor/autoload.php');

// load default config from this folder
require(__DIR__ . '/config.php');

// load application specific config from application that uses this as-vendor-application
if (is_readable($application_dir . '/app/config.php')) {
    require($application_dir . '/app/config.php');
}

// load autoload registry for module namespaces
$autoloads_include = $application_dir . '/app/config/includes/autoload.php';
if (is_readable($autoloads_include)) {
    require($autoloads_include);
}

// make sure that we are always shown errors within dev environments
if (preg_match('/^development.*/', AgaviConfig::get('core.clean_environment'))) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// bootstrap the app
Agavi::bootstrap(AgaviConfig::get('core.environment'));

// load local settings (from APP_LOCAL_CONFIG_DIR)
require AgaviConfigCache::checkConfig($application_dir . '/app/config/local_configuration.xml');
