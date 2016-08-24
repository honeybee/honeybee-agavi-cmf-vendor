<?php

$composer = require 'vendor/autoload.php';
$composer->addClassMap([
    'AgaviPhpUnitCli' => 'vendor/honeybee/agavi/src/testing/AgaviPhpUnitCli.class.php',
]);

putenv('APP_LOCAL_CONFIG_DIR=/tmp');
putenv('APP_DIR=' . realpath(__DIR__ . '/../'));
putenv('APP_ENV=testing');
putenv('APP_CONTEXT=web');

require(__DIR__ . '/../app/config.php');

AgaviConfig::set('core.testing_dir', __DIR__);

AgaviPhpUnitCli::dispatch($_SERVER['argv']);
