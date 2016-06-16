<?php

use Honeybee\Projection\ProjectionTypeMap;
use Workflux\Builder\XmlStateMachineBuilder;

$projection_type_map = new ProjectionTypeMap();
$projection_type_map->setItem(
    'honeybee.system_account.user',
    new Honeybee\SystemAccount\User\Projection\Standard\UserType(
        (new XmlStateMachineBuilder([
            'name' => 'honeybee_system_account_user_workflow_default',
            'state_machine_definition' => AgaviConfig::get('core.app_dir') . '/modules/Honeybee_SystemAccount/config/User/workflows.xml'
        ]))->build()
    )
);

return $projection_type_map;
