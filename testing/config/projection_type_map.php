<?php

use Honeybee\Projection\ProjectionTypeMap;
use Workflux\Builder\XmlStateMachineBuilder;

$module_dir = AgaviConfig::get('core.module_dir');
$testing_dir = AgaviConfig::get('core.testing_dir');

$projection_types = [];

$projection_types['honeybee.system_account.user::projection.standard'] =
    new Honeybee\SystemAccount\User\Projection\Standard\UserType();
$projection_types['honeybee-cmf.test_fixtures.author::projection.standard'] =
    new Honeybee\Tests\Fixture\BookSchema\Projection\Author\AuthorType();
$projection_types['honeybee-cmf.test_fixtures.book::projection.standard'] =
    new Honeybee\Tests\Fixture\BookSchema\Projection\Book\BookType();
$projection_types['honeybee-cmf.test_fixtures.publication::projection.standard'] =
    new Honeybee\Tests\Fixture\BookSchema\Projection\Publication\PublicationType();
$projection_types['honeybee-cmf.test_fixtures.publisher::projection.standard'] =
    new Honeybee\Tests\Fixture\BookSchema\Projection\Publisher\PublisherType();

return new ProjectionTypeMap($projection_types);
