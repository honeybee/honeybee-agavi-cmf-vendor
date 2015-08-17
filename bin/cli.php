<?php

// display errors on CLI
error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 'On');

// set default context of CLI application
$default_context = 'console';

// no modifier for the environment name
$environment_modifier = '';

// special environment for basic operations of the application
if (in_array('--recovery', $argv)) {
    putenv('AGAVI_ENVIRONMENT=recovery');
}

// application directory must be readable
$application_dir = getenv('APPLICATION_DIR');
if ($application_dir === false
    || realpath($application_dir) === false
    || !is_readable($application_dir)
) {
    echo('APPLICATION_DIR not found or not readable: ' . $application_dir . PHP_EOL);
    exit(1);
}

// bootstrap file must be readable
$bootstrap_file = getenv('BOOTSTRAP_PHP_FILE');
if ($bootstrap_file === false
    || realpath($bootstrap_file) === false
    || !is_readable($bootstrap_file)
) {
    echo('BOOTSTRAP_PHP_FILE not found or not readable: ' . $bootstrap_file . PHP_EOL);
    exit(1);
}

// bootstrap application (autoloading, basic settings etc.)
require($bootstrap_file);

unset($application_dir, $bootstrap_file, $default_context, $environment_modifier);

// dispatch application
$response = AgaviContext::getInstance()->getController()->dispatch();
exit($response->getExitCode());

