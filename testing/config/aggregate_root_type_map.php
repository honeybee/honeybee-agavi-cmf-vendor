<?php

use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Workflux\Builder\XmlStateMachineBuilder;

$module_dir = AgaviConfig::get('core.module_dir');
$testing_dir = AgaviConfig::get('core.testing_dir');

$aggregate_root_types = [];

$aggregate_root_types['honeybee.system_account.user'] =
    new Honeybee\SystemAccount\User\Model\Aggregate\UserType();
$aggregate_root_types['honeybee_cmf.test_fixtures.author'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Author\AuthorType();
$aggregate_root_types['honeybee_cmf.test_fixtures.book'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Book\BookType();
$aggregate_root_types['honeybee_cmf.test_fixtures.publication'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Publication\PublicationType();
$aggregate_root_types['honeybee_cmf.test_fixtures.publisher'] =
    new Honeybee\Tests\Fixture\BookSchema\Model\Publisher\PublisherType();

return new AggregateRootTypeMap($aggregate_root_types);
