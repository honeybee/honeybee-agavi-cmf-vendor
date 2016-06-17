<?php

use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Workflux\Builder\XmlStateMachineBuilder;

$aggregate_root_type_map = new AggregateRootTypeMap;
$aggregate_root_type_map->setItem(
    'honeybee.system_account.user',
    new Honeybee\SystemAccount\User\Model\Aggregate\UserType(
        (new XmlStateMachineBuilder([
            'name' => 'honeybee_system_account_user_workflow_default',
            'state_machine_definition' => AgaviConfig::get('core.app_dir') . '/modules/Honeybee_SystemAccount/config/User/workflows.xml'
        ]))->build()
    )
);

return $aggregate_root_type_map;
