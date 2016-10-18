<?php

$workflow_configs = include(AgaviConfig::get('core.config_dir') . '/includes/workflow_configs.php');

$workflow_configs[] = AgaviConfig::get('core.testing_dir') . '/Fixture/BookSchema/Model/workflows.xml';

return $workflow_configs;
