<?php

$default_context = 'web';
$baseDir = dirname(dirname(__DIR__));
require $baseDir . '/app/bootstrap.php';

// return SAMI configuration for generation of API documentation
return new Sami\Sami($baseDir . '/app/lib/Honeybee', array(
    'title'                 => 'Honeybee API',
    'theme'                 => 'enhanced',
    'default_opened_level'  => 2,
    'build_dir'             => $baseDir . '/etc/integration/build/docs/api/serverside',
    'cache_dir'             => $baseDir . '/etc/integration/build/docs/api/serverside/cache'
));
