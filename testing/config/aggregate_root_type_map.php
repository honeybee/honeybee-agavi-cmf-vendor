<?php

use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Workflux\Builder\XmlStateMachineBuilder;

$module_dir = AgaviConfig::get('core.module_dir');
$testing_dir = AgaviConfig::get('core.testing_dir');

$aggregate_root_types = [];

$aggregate_root_types['honeybee.system_account.user'] =
    new Honeybee\SystemAccount\User\Model\Aggregate\UserType(
        (new XmlStateMachineBuilder([
            'name' => 'honeybee_system_account_user_workflow_default',
            'state_machine_definition' => $module_dir . '/Honeybee_SystemAccount/config/User/workflows.xml'
        ]))->build()
    );
$aggregate_root_types['honeybee-cmf.test_fixtures.author'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Author\AuthorType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => $testing_dir . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    );
$aggregate_root_types['honeybee-cmf.test_fixtures.book'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Book\BookType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => $testing_dir . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    );
$aggregate_root_types['honeybee-cmf.test_fixtures.publication'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Publication\PublicationType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => $testing_dir . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    );
$aggregate_root_types['honeybee-cmf.test_fixtures.publisher'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Publisher\PublisherType(
        (new XmlStateMachineBuilder([
            'name' => 'author_workflow_default',
            'state_machine_definition' => $testing_dir . '/Fixture/BookSchema/Model/workflows.xml'
        ]))->build()
    );

return new AggregateRootTypeMap($aggregate_root_types);
