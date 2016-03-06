<?php

require 'vendor/autoload.php';

$app_autoload_include = 'app/config/includes/autoload.php';
if (is_readable($app_autoload_include)) {
    require($app_autoload_include);
}
