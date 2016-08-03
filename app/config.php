<?php

// determine local configuration directory
$local_config_dir = @$local_config_dir ?: getenv('APP_LOCAL_CONFIG_DIR');
if (empty($local_config_dir)) {
    throw new Exception('Environment variable "APP_LOCAL_CONFIG_DIR" has not been set.');
}

// determine application directory
$application_dir = @$application_dir ?: getenv('APP_DIR');
if (empty($application_dir)) {
    throw new Exception('APP_DIR not set. Application probably not set up correctly.');
}

$application_dir = realpath($application_dir);
$local_config_dir = realpath($local_config_dir);
$vendor_dir = $application_dir . '/vendor';
$agavi_dir = $vendor_dir . '/agavi/agavi/src';
$honeybee_dir = $vendor_dir . '/honeybee/honeybee-agavi-cmf-vendor';

// environment name to use
$environment = @$environment ?: getenv('APP_ENV');
if (empty($environment)) {
    throw new Exception('APP_ENV is not set.');
}
AgaviConfig::set('core.clean_environment', $environment);

// environment modifier (suffix)
$environment_modifier = @$environment_modifier ?: getenv('APP_ENV_MODIFIER');
if (empty($environment_modifier)) {
    $environment_modifier = '';
}
AgaviConfig::set('core.environment_modifier', $environment_modifier);
AgaviConfig::set('local.environment_modifier', $environment_modifier);

// complete environment name
$environment .= $environment_modifier;

// register 'core.*' settings
AgaviConfig::set('core.environment', $environment);
AgaviConfig::set('core.agavi_dir', $agavi_dir);
AgaviConfig::set('core.app_dir', $application_dir . '/app');
AgaviConfig::set('core.pub_dir', $application_dir . '/pub');
AgaviConfig::set('core.local_config_dir', $local_config_dir);
AgaviConfig::set('core.config_dir', AgaviConfig::get('core.app_dir') . '/config');
AgaviConfig::set('core.modules_dir', AgaviConfig::get('core.app_dir') . '/modules');
AgaviConfig::set('core.module_dir', AgaviConfig::get('core.app_dir') . '/modules');
AgaviConfig::set('core.model_dir', AgaviConfig::get('core.app_dir') . '/model');
AgaviConfig::set('core.lib_dir', AgaviConfig::get('core.app_dir') . '/lib');
AgaviConfig::set('core.template_dir', AgaviConfig::get('core.app_dir') . '/templates');
// e,g, necessary for RecoveryConsoleRouting (in factories.xml) to find correct app/config/recovery/routing.xml file
if (__DIR__ === AgaviConfig::get('core.app_dir')) {
    AgaviConfig::set('core.honeybee_dir', $application_dir); //used in resourcepacker; has to be changed
    AgaviConfig::set('core.cms_dir', $application_dir);
    AgaviConfig::set('project.dir', $application_dir);
} else {
    AgaviConfig::set('core.honeybee_dir', $honeybee_dir);
    AgaviConfig::set('core.cms_dir', $application_dir);
    AgaviConfig::set('project.dir', $application_dir);
}
// always points to the honeybee config dir (whether honeybee runs standalone or as vendor lib)
AgaviConfig::set('core.honeybee_config_dir', AgaviConfig::get('core.honeybee_dir') . '/app/config');
// points to the templates that honeybee has built-in
AgaviConfig::set('core.honeybee_template_dir', AgaviConfig::get('core.honeybee_dir') . '/app/templates');
// AgaviConfig::set('project.templates_dir', AgaviConfig::get('project.dir') . '/app/templates'); // see twigrenderer

// always points to the honeybee skeleton templates lookup dir (whether honeybee runs standalone or as vendor lib)
AgaviConfig::set('core.honeybee_skeleton_dir', AgaviConfig::get('core.honeybee_dir') . '/dev/skeletons');
// project's skeleton lookup dir
AgaviConfig::set('core.skeleton_dir', AgaviConfig::get('core.cms_dir') . '/dev/skeletons');
// all skeleton lookup locations
AgaviConfig::set(
    'core.skeleton_dirs',
    [ AgaviConfig::get('core.skeleton_dir'), AgaviConfig::get('core.honeybee_skeleton_dir') ]
);

// allow a custom cache directory location
$cache_dir = getenv('APP_CACHE_DIR');
//$cache_dir = '/dev/shm/cache';
if ($cache_dir === false) {
    // default cache directory takes environment into account to mitigate cases
    // where the environment on a server is switched and the cache isn't cleared
    AgaviConfig::set(
        'core.cache_dir',
        AgaviConfig::get('core.app_dir') . '/cache', // . AgaviConfig::get('core.environment'),
        true, // overwrite
        true // readonly
    );
    AgaviConfig::set(
        'core.cache_dir_without_env',
        AgaviConfig::get('core.app_dir') . '/cache',
        true,
        true
    );
} else {
    // use cache directory given by environment variable
    $cache_dir = realpath($cache_dir);
    AgaviConfig::set('core.cache_dir', $cache_dir, true, true); // overwrite, readonly
    AgaviConfig::set('core.cache_dir_without_env', $cache_dir, true, true);
}

// contexts are e.g. 'web', 'console', 'soap' or 'xmlrpc'
$default_context = @$default_context ?: getenv('APP_CONTEXT');
if (!$default_context) {
    throw new RuntimeException('Missing default context setting or APP_CONTEXT environment variable.');
}
// this is one of the most important settings for agavi
AgaviConfig::set('core.default_context', $default_context);

// some default timezone should always be set
date_default_timezone_set('Europe/Berlin');
