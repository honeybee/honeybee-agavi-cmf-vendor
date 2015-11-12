<?php

$default_context = @$default_context ?: 'web';
$environment_modifier = @$environment_modifier ?: '';

$application_dir = getenv('APPLICATION_DIR');
if ($application_dir === false || realpath($application_dir) === false || !is_readable($application_dir)) {
    if (!putenv('APPLICATION_DIR=' . realpath(__DIR__ . '/../'))) {
        error_log('Application directory could not be set via putenv.');
        throw new Exception('Application directory could not be set.');
    }
}

$local_config_dir = getenv('HONEYBEE_LOCAL_CONFIG_DIR');
if ($local_config_dir === false || realpath($local_config_dir) === false || !is_readable($local_config_dir)) {
    $local_config_dir = '/usr/local/' . basename($application_dir);
    if (!putenv('HONEYBEE_LOCAL_CONFIG_DIR=' . realpath($local_config_dir))) {
        error_log('Local config directory could not be set via putenv.');
        throw new Exception('Local config directory could not be set.');
    }
}

$bootstrap_file = getenv('BOOTSTRAP_PHP_FILE') ?: realpath(__DIR__ . '/../app/bootstrap.php');
if (realpath($bootstrap_file) === false || !is_readable($bootstrap_file)) {
    throw new Exception('No bootstrap file configured for application.');
}

require($bootstrap_file);
unset($application_dir, $bootstrap_file, $default_context, $environment_modifier);

AgaviContext::getInstance()->getController()->dispatch();
