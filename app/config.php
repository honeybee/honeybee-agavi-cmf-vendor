<?php

// get application directory
$application_dir = getenv('APPLICATION_DIR');
if ($application_dir === false) {
    throw new \Exception('APPLICATION_DIR not set. Application probably not set up correctly.');
}
$application_dir = realpath($application_dir);
$vendor_dir = $application_dir . DIRECTORY_SEPARATOR . 'vendor';
$agavi_dir = $vendor_dir . str_replace('/', DIRECTORY_SEPARATOR, '/agavi/agavi/src');
$honeybee_dir = $vendor_dir . str_replace('/', DIRECTORY_SEPARATOR, '/honeybee/honeybee-agavi-cmf-vendor');

// autoload all vendor libs and agavi in particular
require($vendor_dir . DIRECTORY_SEPARATOR . 'autoload.php');
//require($agavi_dir . DIRECTORY_SEPARATOR . 'agavi.php');

// basic settings necessary to run the application correctly
AgaviConfig::set('core.agavi_dir', $agavi_dir);
AgaviConfig::set('core.app_dir', $application_dir . DIRECTORY_SEPARATOR . 'app');
AgaviConfig::set('core.pub_dir', $application_dir . DIRECTORY_SEPARATOR . 'pub');
AgaviConfig::set('core.config_dir', AgaviConfig::get('core.app_dir') . DIRECTORY_SEPARATOR . 'config');
AgaviConfig::set('core.modules_dir', AgaviConfig::get('core.app_dir') . DIRECTORY_SEPARATOR . 'modules');
AgaviConfig::set('core.module_dir', AgaviConfig::get('core.app_dir') . DIRECTORY_SEPARATOR . 'modules');
AgaviConfig::set('core.model_dir', AgaviConfig::get('core.app_dir') . DIRECTORY_SEPARATOR . 'model');
AgaviConfig::set('core.lib_dir', AgaviConfig::get('core.app_dir') . DIRECTORY_SEPARATOR . 'lib');
AgaviConfig::set('core.template_dir', AgaviConfig::get('core.app_dir') . DIRECTORY_SEPARATOR . 'templates');

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
AgaviConfig::set(
    'core.honeybee_config_dir',
    AgaviConfig::get('core.honeybee_dir') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config'
);

// points to the templates that honeybee has built-in
AgaviConfig::set('core.honeybee_template_dir', AgaviConfig::get('core.honeybee_dir') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'templates');

//AgaviConfig::set('project.templates_dir', AgaviConfig::get('project.dir') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'templates'); // see twigrenderer


// always points to the honeybee skeleton templates lookup dir (whether honeybee runs standalone or as vendor lib)
AgaviConfig::set(
    'core.honeybee_skeleton_dir',
    AgaviConfig::get('core.honeybee_dir') . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'skeletons'
);
// project's skeleton lookup dir
AgaviConfig::set(
    'core.skeleton_dir',
    AgaviConfig::get('core.cms_dir') . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'skeletons'
);
// all skeleton lookup locations
AgaviConfig::set('core.skeleton_dirs', array(AgaviConfig::get('core.skeleton_dir'), AgaviConfig::get('core.honeybee_skeleton_dir')));

// some default timezone should always be set
date_default_timezone_set('Europe/Berlin');

