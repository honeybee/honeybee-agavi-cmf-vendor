<?php

$default_context = @$default_context ?: 'web';

$environment_modifier='-stateless-api';

// application dir is either set via (env) variable or will be ./../
$application_dir = @$application_dir ?: getenv('APP_DIR');
if (empty($application_dir) || realpath($application_dir) === false || !is_readable($application_dir)) {
    $application_dir = realpath(__DIR__ . '/../');
    if (empty($application_dir) || !putenv('APP_DIR=' . $application_dir)) {
        throw new Exception('APP_DIR not set');
    }
}

// bootstrap file sets initial configuration
$bootstrap_file = @$bootstrap_file ?: getenv('APP_BOOTSTRAP_PHP_FILE');
if (empty($bootstrap_file) || realpath($bootstrap_file) === false || !is_readable($bootstrap_file)) {
    $bootstrap_file = realpath(__DIR__ . '/../app/bootstrap.php');
    if ($bootstrap_file === false || !is_readable($bootstrap_file)) {
        throw new Exception('Application bootstrap file could not be read.');
    }
}

// bootstrap application
require($bootstrap_file);
unset(
    $application_dir,
    $bootstrap_file,
    $local_config_dir,
    $default_context,
    $environment,
    $environment_modifier,
    $msg,
    $environment_file
);

AgaviContext::getInstance()->getController()->dispatch();
