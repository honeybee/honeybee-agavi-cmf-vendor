<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 'On');

$default_context = 'console';
$environment_modifier = '';
// special environment for basic operations of the application
if (in_array('--recovery', $argv)) {
    putenv('AGAVI_ENVIRONMENT=recovery');
}

$application_dir = getenv('APPLICATION_DIR');
if ($application_dir === false || realpath($application_dir) === false || !is_readable($application_dir)) {
    echo('APPLICATION_DIR not found or not readable: ' . $application_dir . PHP_EOL);
    exit(1);
}

$local_config_dir = getenv('HONEYBEE_LOCAL_CONFIG_DIR');
if ($local_config_dir === false || realpath($local_config_dir) === false || !is_readable($local_config_dir)) {
    $local_config_dir = getenv("HOME") . '/.local/' . basename($application_dir);
    if (!putenv('HONEYBEE_LOCAL_CONFIG_DIR=' . realpath($local_config_dir))) {
        error_log('Local config directory could not be set via putenv.');
        throw new Exception('Local config directory could not be set.');
    }
}

$bootstrap_file = getenv('BOOTSTRAP_PHP_FILE');
if ($bootstrap_file === false || realpath($bootstrap_file) === false || !is_readable($bootstrap_file)) {
    echo('BOOTSTRAP_PHP_FILE not found or not readable: ' . $bootstrap_file . PHP_EOL);
    exit(1);
}
require($bootstrap_file);
unset($application_dir, $bootstrap_file, $default_context, $environment_modifier);

$response = AgaviContext::getInstance()->getController()->dispatch();
exit($response->getExitCode());
