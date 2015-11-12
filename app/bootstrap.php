<?php

// make generated files group writeable for easy switch between web/console
umask(02);

$application_dir = getenv('APPLICATION_DIR');
if ($application_dir === false || !is_dir(realpath($application_dir))) {
    throw new Exception('APPLICATION_DIR not set or not a directory. Aborting.');
}

// load regular composer autoload
require($application_dir . '/vendor/autoload.php');

// load config from this folder
require(__DIR__ . '/config.php');

// load config from application that uses this as-vendor-application
if (is_readable($application_dir . '/app/config.php')) {
    require($application_dir . '/app/config.php');
}
// load autoload registry for our module namespaces
$autoloads_include = $application_dir . str_replace('/', DIRECTORY_SEPARATOR, '/app/config/includes/autoload.php');
if (is_readable($autoloads_include)) {
    require($autoloads_include);
}

// make sure that we are always shown errors within dev environments
if (preg_match('/.*development.*/', AgaviConfig::get('core.clean_environment'))) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL ^ E_STRICT);
}

// bootstrap the app
Agavi::bootstrap(AgaviConfig::get('core.environment'));

// load local settings
require AgaviConfigCache::checkConfig($application_dir . '/app/config/local_configuration.xml');
