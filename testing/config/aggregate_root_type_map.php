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
$aggregate_root_type_map->setItem(
    'honeybee-cmf.aggregate_fixtures.author',
    new Honeybee\Tests\Fixture\BookSchema\Model\Author\AuthorType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => AgaviConfig::get('core.testing_dir') . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    )
);
$aggregate_root_type_map->setItem(
    'honeybee-cmf.aggregate_fixtures.book',
    new Honeybee\Tests\Fixture\BookSchema\Model\Book\BookType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => AgaviConfig::get('core.testing_dir') . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    )
);
$aggregate_root_type_map->setItem(
    'honeybee-cmf.aggregate_fixtures.publication',
    new Honeybee\Tests\Fixture\BookSchema\Model\Publication\PublicationType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => AgaviConfig::get('core.testing_dir') . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    )
);
$aggregate_root_type_map->setItem(
    'honeybee-cmf.aggregate_fixtures.publisher',
    new Honeybee\Tests\Fixture\BookSchema\Model\Publisher\PublisherType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => AgaviConfig::get('core.testing_dir') . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    )
);

return $aggregate_root_type_map;
