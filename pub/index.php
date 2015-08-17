<?php

//$time_start = microtime(true);

/*
$_SERVER["SERVER_NAME"] = "192.168.46.63";
$_SERVER["SERVER_PORT"] = "6080";
$_SERVER["HTTP_HOST"] = "192.168.46.63:6080";
$_SERVER["HTTP_REFERER"] = "http://192.168.46.63:6080/";
ini_set('xdebug.var_display_max_depth', 4);
*/

// ----------------------------------------------------------------------------
$default_context = 'web';
$environment_modifier = '';

// application directory must be readable
$application_dir = getenv('APPLICATION_DIR');
if ($application_dir === false
    || realpath($application_dir) === false
    || !is_readable($application_dir)
) {
    if (!putenv('APPLICATION_DIR=' . realpath(__DIR__ . '/../'))) {
        error_log('Application directory could not be set via putenv.');
        throw new Exception('Application directory could not be set.');
    }
}

// bootstrap file must be readable
$bootstrap_file = getenv('BOOTSTRAP_PHP_FILE');
if ($bootstrap_file === false) {
    $bootstrap_file = realpath(__DIR__ . '/../app/bootstrap.php');
}

if (realpath($bootstrap_file) === false || !is_readable($bootstrap_file)) {
    throw new Exception('No bootstrap file configured for application.');
}

// bootstrap application (autoloading, basic settings etc.)
require($bootstrap_file);

unset($application_dir, $bootstrap_file, $default_context, $environment_modifier);

AgaviContext::getInstance()->getController()->dispatch();

// ----------------------------------------------------------------------------
/*
$memory_available = filter_var(ini_get("memory_limit"), FILTER_SANITIZE_NUMBER_INT);
$peak_memory = memory_get_peak_usage(false) / 1024 / 1024;
$percentage = ($peak_memory / $memory_available) * 100;
$time_end = microtime(true);
error_log(
    sprintf(
        "reqtime=%0.3fs peakmem=%0.3f MiB percentage=%0.3f of %0.3f MiB available",
        $time_end - $time_start,
        $peak_memory,
        $percentage,
        $memory_available
    )
);
*/
