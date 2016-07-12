<?php

namespace Honeybee\Tests\Unit\FrameworkBinding\Agavi\Validator;

use AgaviValidationReportQuery;
use AgaviWebRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\Request\HoneybeeUploadedFile;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootCommandValidator;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Model\Task\TaskConflict;
use Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand;
use Honeybee\Tests\Mock\HoneybeeAgaviUnitTestCase;
use Mockery;

class AggregateRootCommandValidatorTest extends HoneybeeAgaviUnitTestCase
{
    protected $vm;

    protected $filesystem_service;

    protected $mock_query_service;

    // need to run in isolation for manageable mock service expectations
    protected $runTestInSeparateProcess = true;

    public function setUp()
    {
        $this->vm = $this->getContext()->createInstanceFor('validation_manager');
        $this->vm->clear();
        $this->filesystem_service = $this->getContext()->getServiceLocator()->getFilesystemService();
        $this->filesystem_service->clear();

        $service_locator = $this->getContext()->getServiceLocator();

        // setup event history for test cases
        $this->mock_query_service = Mockery::mock(QueryServiceInterface::CLASS);
        $mock_query_service_map = new QueryServiceMap([
            'honeybee-cmf.test_fixtures.author::query_service' => $this->mock_query_service
        ]);

        $service_locator->prepareService(
            'honeybee.infrastructure.data_access_service',
            [ ':query_service_map' => $mock_query_service_map ]
        );
    }

    protected function prepareHistory(
        $fixture,
        $identifier = 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1'
    ) {
        $events = require(__DIR__ . '/Fixture/' . $fixture . '.php');
        $this->mock_query_service
            ->shouldReceive('findEventsByIdentifier')
            ->once()
            ->with($identifier)
            ->andReturn(new FinderResult($events, count($events)));
    }

    protected function createValidator($base = 'edit')
    {
        return $this->vm->createValidator(
            AggregateRootCommandValidator::CLASS,
            [ '__submit' ],
            [
                '' => 'Invalid command payload given.',
                'conflict_detected' => 'Data has changed and conflicts with your attempt to modify it.',
                'email.invalid_format' => 'Email has an invalid format.',
                'firstname.min_length' => 'Firstname is too short.',
                'firstname.max_length' => 'Firstname is too long.',
                'products.highlight.title.min_length' => 'Title is too short.',
                'no_image' => 'Uploaded image not found.',
                'images.copyright_url.host_missing' => 'Missing host for copyright-url',
                'invalid_revision' => 'Revision does not match sequence.',
                'missing_revision' => 'Revision is missing.',
                'history_is_empty' => 'History is missing.',
                'invalid_payload' => 'Payload is invalid.'
            ],
            [
                'name' => 'invalid_task_data',
                'base' => $base,
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'identifier_arg' => 'identifier',
                'command_implementor' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand',
                'attribute_blacklist' => [ 'token', 'products.highlight.ignored' ]
            ]
        );
    }

    public function testExecute()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'token' => 'ignored',
                    'images' => [ [] ] // removing empty images
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'edit', 'command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('command');
        $this->assertInstanceOf(ModifyAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand',
                'values' => [
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'images' => [],
                ],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => [],
                'known_revision' => 1
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithInvalidSource()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'firstname' => 'x',
                    'lastname' => 'Hunt',
                    'email' => 'invalidtest.com',
                    'products' => [
                        [
                            '@type' => 'highlight',
                            'title' => 'x'
                        ],
                        [
                            '@type' => 'highlight',
                            'title' => 'y'
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(5, $query->count());
        $this->assertEquals(
            [ 'Firstname is too short.' ],
            $query->byArgument('edit[firstname]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Email has an invalid format.' ],
            $query->byArgument('edit[email]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Title is too short.' ],
            $query->byArgument('edit[products][0][title]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Title is too short.' ],
            $query->byArgument('edit[products][1][title]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Payload is invalid.' ],
            $query->byArgument('edit[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithUnknownIdentifier()
    {
        $validator = $this->createValidator();
        $this->prepareHistory(
            'aggregate_empty',
            'honeybee-cmf.test_fixtures.author-12345678-251e-4a17-947a-dd3987e5a9df-de_DE-1'
        );

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-12345678-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'uuid' => '12345678-251e-4a17-947a-dd3987e5a9df'
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->assertEquals(
            [ 'History is missing.' ],
            $query->byArgument('edit[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithMissingRevision()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com'
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->assertEquals(
            [ 'Revision is missing.' ],
            $query->byArgument('edit[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithInvalidRevision()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 5,
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com'
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->assertEquals(
            [ 'Revision does not match sequence.' ],
            $query->byArgument('edit[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithMergeableData()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_modified');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'change.user@test.com'
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'edit', 'command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('command');
        $this->assertInstanceOf(ModifyAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand',
                'values' => [
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'change.user@test.com'
                ],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => [],
                'known_revision' => 1
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithConflictingData()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_modified');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'firstname' => 'Conflict',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com'
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->assertEquals(
            [ 'Data has changed and conflicts with your attempt to modify it.' ],
            $query->byArgument('edit[__submit]')->getErrorMessages()
        );

        $task_service = $this->getContext()->getServiceLocator()->getTaskService();
        $this->assertTrue($task_service->hasTaskConflicts());
        $task_conflicts = $task_service->getTaskConflicts();
        $this->assertCount(1, $task_conflicts);
        $task_conflict = $task_service->getLastTaskConflict();
        $this->assertInstanceOf(TaskConflict::CLASS, $task_conflict);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Model\Task\TaskConflict',
                'current_resource' => [
                    '@type' => 'honeybee-cmf.test_fixtures.author',
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 2,
                    'uuid' => '63d0d3f0-251e-4a17-947a-dd3987e5a9df',
                    'language' => 'de_DE',
                    'version' => 1,
                    'created_at' => '2016-06-21T08:39:39.837732+00:00',
                    'modified_at' => '2016-06-21T09:40:40.123456+00:00',
                    'workflow_state' => 'inactive',
                    'workflow_parameters' => [],
                    'metadata' => [],
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'blurb' => 'the grinch',
                    'token' => '7734ad2c6332fd0503afb3213c817391b93cb078',
                    'images' => [
                        [
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'filesize' => 116752,
                            'filename' => 'kitty.jpg',
                            'mimetype' => 'image/jpeg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' => '',
                            'width' => 1200,
                            'height' => 1200,
                            'aoi' => '',
                            'metadata' => []
                        ]
                    ],
                    'products' => [
                        [
                            '@type' => 'highlight',
                            'identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                            'title' => 'Awesome',
                            'description' => '',
                            'ignored' => ''
                        ],
                        [
                            '@type' => 'highlight',
                            'identifier' => '6d1460a7-c8ca-4fd9-89a2-3991a0766b14',
                            'title' => 'Amaze',
                            'description' => '',
                            'ignored' => ''
                        ]
                    ],
                    'books' => [
                        [
                            '@type' => 'book',
                            'identifier' => 'a4be230b-fdd2-49f2-b943-f5b5b17a0d61',
                            'referenced_identifier' => 'honeybee-cmf.test_fixtures.book-e5e61e60-67de-405b-9e10-e8c2f9455f3f-de_DE-1',
                            'title' => 'ZAMM'
                        ]
                    ]
                ],
                'conflicted_resource' => [
                    '@type' => 'honeybee-cmf.test_fixtures.author',
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'uuid' => '63d0d3f0-251e-4a17-947a-dd3987e5a9df',
                    'language' => 'de_DE',
                    'version' => 1,
                    'created_at' => null,
                    'modified_at' => null,
                    'workflow_state' => 'inactive',
                    'workflow_parameters' => [],
                    'metadata' => [],
                    'firstname' => 'Conflict',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'blurb' => 'the grinch',
                    'token' => '7734ad2c6332fd0503afb3213c817391b93cb078',
                    'images' => [
                        [
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'filesize' => 116752,
                            'filename' => 'kitty.jpg',
                            'mimetype' => 'image/jpeg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' => '',
                            'width' => 1200,
                            'height' => 1200,
                            'aoi' => '',
                            'metadata' => []
                        ]
                    ],
                    'products' => [],
                    'books' => []
                ],
                'conflicting_events' => [
                    [
                        '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\AuthorModifiedEvent',
                        'data' => [
                            'firstname' => 'Mark',
                            'lastname' => 'Hunt'
                        ],
                        'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                        'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                        'embedded_entity_events' => [
                            [
                                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent',
                                'data' => [
                                    '@type' => 'highlight',
                                    'identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                                    'title' => 'Awesome'
                                ],
                                'position' => 0,
                                'embedded_entity_identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                                'embedded_entity_type' => 'highlight',
                                'parent_attribute_name' => 'products',
                                'embedded_entity_events' => []
                            ],
                            [
                                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent',
                                'data' => [
                                    '@type' => 'highlight',
                                    'identifier' => '6d1460a7-c8ca-4fd9-89a2-3991a0766b14',
                                    'title' => 'Amaze'
                                ],
                                'position' => 1,
                                'embedded_entity_identifier' => '6d1460a7-c8ca-4fd9-89a2-3991a0766b14',
                                'embedded_entity_type' => 'highlight',
                                'parent_attribute_name' => 'products',
                                'embedded_entity_events' => []
                            ],
                            [
                                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent',
                                'data' => [
                                    '@type' => 'book',
                                    'identifier' => 'a4be230b-fdd2-49f2-b943-f5b5b17a0d61',
                                    'referenced_identifier' => 'honeybee-cmf.test_fixtures.book-e5e61e60-67de-405b-9e10-e8c2f9455f3f-de_DE-1',
                                    'title' => 'ZAMM'
                                ],
                                'position' => 0,
                                'embedded_entity_identifier' => 'a4be230b-fdd2-49f2-b943-f5b5b17a0d61',
                                'embedded_entity_type' => 'book',
                                'parent_attribute_name' => 'books',
                                'embedded_entity_events' => []
                            ]
                        ],
                        'seq_number' => 2,
                        'uuid' => '7cc9d8d9-860c-49cd-961d-a9d15e214abd',
                        'iso_date' => '2016-06-21T09:40:40.123456+00:00',
                        'metadata' => [
                            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
                            'role' => 'administrator'
                        ]
                    ]
                ],
                'conflicting_attribute_names' => [ 'firstname' ]
            ],
            $task_conflict->toArray()
        );
    }

    public function testExecuteWithWorkflowConflict()
    {
        $validator = $this->createValidator();
        $validator->setParameter('identifier_arg', 'custom_id');
        $validator->setParameter('revision_arg', 'custom_rev');
        $this->prepareHistory('aggregate_proceeded');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'uuid' => '63d0d3f0-251e-4a17-947a-dd3987e5a9df',
                    'custom_id' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'custom_rev' => 2,
                    'workflow_state' => 'inactive',
                    'language' => 'de_DE'
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->assertEquals(
            [ 'Data has changed and conflicts with your attempt to modify it.' ],
            $query->byArgument('edit[__submit]')->getErrorMessages()
        );

        $task_service = $this->getContext()->getServiceLocator()->getTaskService();
        $this->assertTrue($task_service->hasTaskConflicts());
        $task_conflicts = $task_service->getTaskConflicts();
        $this->assertCount(1, $task_conflicts);
        $task_conflict = $task_service->getLastTaskConflict();
        $this->assertInstanceOf(TaskConflict::CLASS, $task_conflict);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Model\Task\TaskConflict',
                'current_resource' => [
                    '@type' => 'honeybee-cmf.test_fixtures.author',
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 3,
                    'uuid' => '63d0d3f0-251e-4a17-947a-dd3987e5a9df',
                    'language' => 'de_DE',
                    'version' => 1,
                    'created_at' => '2016-06-21T08:39:39.837732+00:00',
                    'modified_at' => '2016-06-21T09:45:40.123456+00:00',
                    'workflow_state' => 'active',
                    'workflow_parameters' => [],
                    'metadata' => [],
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'blurb' => 'the grinch',
                    'token' => '7734ad2c6332fd0503afb3213c817391b93cb078',
                    'images' => [
                        [
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'filesize' => 116752,
                            'filename' => 'kitty.jpg',
                            'mimetype' => 'image/jpeg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' => '',
                            'width' => 1200,
                            'height' => 1200,
                            'aoi' => '',
                            'metadata' => []
                        ]
                    ],
                    'products' => [
                        [
                            '@type' => 'highlight',
                            'identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                            'title' => 'Awesome',
                            'description' => '',
                            'ignored' => ''
                        ]
                    ],
                    'books' => []
                ],
                'conflicted_resource' => [
                    '@type' => 'honeybee-cmf.test_fixtures.author',
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 2,
                    'uuid' => '63d0d3f0-251e-4a17-947a-dd3987e5a9df',
                    'language' => 'de_DE',
                    'version' => 1,
                    'created_at' => null,
                    'modified_at' => null,
                    'workflow_state' => 'inactive',
                    'workflow_parameters' => [],
                    'metadata' => [],
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'blurb' => 'the grinch',
                    'token' => '7734ad2c6332fd0503afb3213c817391b93cb078',
                    'images' => [
                        [
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'filesize' => 116752,
                            'filename' => 'kitty.jpg',
                            'mimetype' => 'image/jpeg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' => '',
                            'width' => 1200,
                            'height' => 1200,
                            'aoi' => '',
                            'metadata' => []
                        ]
                    ],
                    'products' => [
                        [
                            '@type' => 'highlight',
                            'identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                            'title' => 'Awesome',
                            'description' => '',
                            'ignored' => ''
                        ]
                    ],
                    'books' => []
                ],
                'conflicting_events' => [
                    [
                        '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ProceedAuthorWorkflow\AuthorWorkflowProceededEvent',
                        'data' => [
                            'workflow_state' => 'active'
                        ],
                        'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                        'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                        'embedded_entity_events' => [],
                        'seq_number' => 3,
                        'uuid' => '2523c4b4-d2aa-4458-bd34-11a71d3adaba',
                        'iso_date' => '2016-06-21T09:45:40.123456+00:00',
                        'metadata' => [
                            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
                            'role' => 'administrator'
                        ]
                    ]
                ],
                'conflicting_attribute_names' => []
            ],
            $task_conflict->toArray()
        );
    }

    public function testExecuteWithResolvableConflicts()
    {
        // support for retroactive conflict resolution https://github.com/honeybee/honeybee/issues/33
        $this->markTestIncomplete();
    }

    public function testExecuteWithUploadedAndReorderedImages()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'images' => [
                        [
                            '__action' => '__move-down',
                            'location' => __DIR__ . '/Fixture/facepalm.jpg',
                            'title' => 'Facepalm',
                            'caption' => 'Engage',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  '',
                            'filesize' => '0',
                            'mimetype' => 'image/jpeg',
                            'width' => '0',
                            'height' => '0'
                        ],
                        [
                            '__action' => '__duplicate',
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' => '',
                            'aoi' => '',
                            'filename' => 'kitty.jpg',
                            'filesize' => '116752',
                            'mimetype' => 'image/jpeg',
                            'width' => '1200',
                            'height' => '1200',
                        ]
                    ]
                ]
            ],
            AgaviWebRequestDataHolder::SOURCE_FILES => [
                'edit' => [
                    'images' => [
                        [
                            'file' => new HoneybeeUploadedFile([
                                'name' => 'facepalm.jpg',
                                'type' => 'image/jpeg',
                                'size' => 15932,
                                'tmp_name' => __DIR__ . '/Fixture/facepalm.jpg',
                                'error' => UPLOAD_ERR_OK,
                                'is_uploaded_file' => true,
                                'is_moved' => false,
                                'contents' => null,
                                'stream' => null,
                                'honeybee_filesize' => 0,
                                'honeybee_width' => 0,
                                'honeybee_height' => 0
                            ])
                        ],
                        [
                            'file' => new HoneybeeUploadedFile([
                                'name' => '',
                                'type' => '',
                                'size' => 0,
                                'tmp_name' => '',
                                'error' => UPLOAD_ERR_NO_FILE,
                                'is_uploaded_file' => true,
                                'is_moved' => false,
                                'contents' => null,
                                'stream' => null,
                                'honeybee_filesize' => 0,
                                'honeybee_width' => 0,
                                'honeybee_height' => 0
                            ])
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'edit', 'command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $uploaded_images = $this->filesystem_service->getTestResourceUris();
        $command = $rd->getParameter('command');
        $this->assertInstanceOf(ModifyAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand',
                'values' => [
                    'images' => [
                        [
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  'kitty.jpg',
                            'filesize' => 116752,
                            'mimetype' => 'image/jpeg',
                            'width' => 1200,
                            'height' => 1200,
                            'metadata' => []
                        ],
                        [
                            'location' => str_replace('honeybee-cmf.test_fixtures.author.tempfiles://', '', $uploaded_images[0]),
                            'title' => 'Facepalm',
                            'caption' => 'Engage',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  'facepalm.jpg',
                            'filesize' => 15932,
                            'mimetype' => 'image/jpeg',
                            'width' => 310,
                            'height' => 205,
                            'metadata' => []
                        ],
                        [
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  'kitty.jpg',
                            'filesize' => 116752,
                            'mimetype' => 'image/jpeg',
                            'width' => 1200,
                            'height' => 1200,
                            'metadata' => []
                        ]
                    ]
                ],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => [],
                'known_revision' => 1
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithUploadedAndDeletedImages()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 1,
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'images' => [
                        [
                            '__action' => '__delete',
                            'location' => 'honeybee/honeybee-cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
                            'title' => 'Facepalm',
                            'caption' => 'Engage',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' => '',
                            'aoi' => '',
                            'filename' => 'kitty.jpg',
                            'filesize' => '116752',
                            'mimetype' => 'image/jpeg',
                            'width' => '1200',
                            'height' => '1200',
                        ]
                    ]
                ]
            ],
            AgaviWebRequestDataHolder::SOURCE_FILES => [
                'edit' => [
                    'images' => [
                        [
                            'file' => new HoneybeeUploadedFile([
                                'name' => 'facepalm.jpg',
                                'type' => 'image/jpeg',
                                'size' => 15932,
                                'tmp_name' => __DIR__ . '/Fixture/facepalm.jpg',
                                'error' => UPLOAD_ERR_OK,
                                'is_uploaded_file' => true,
                                'is_moved' => false,
                                'contents' => null,
                                'stream' => null,
                                'honeybee_filesize' => 0,
                                'honeybee_width' => 0,
                                'honeybee_height' => 0
                            ])
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'edit', 'command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $uploaded_images = $this->filesystem_service->getTestResourceUris();
        $command = $rd->getParameter('command');
        $this->assertInstanceOf(ModifyAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand',
                'values' => [
                    'images' => [
                        [
                            'location' => str_replace('honeybee-cmf.test_fixtures.author.tempfiles://', '', $uploaded_images[0]),
                            'title' => 'Facepalm',
                            'caption' => 'Engage',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  'facepalm.jpg',
                            'filesize' => 15932,
                            'mimetype' => 'image/jpeg',
                            'width' => 310,
                            'height' => 205,
                            'metadata' => []
                        ]
                    ]
                ],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => [],
                'known_revision' => 1
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithEntityListDeleteAction()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_modified');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 2,
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'products' => [
                        [
                            '__action' => '__delete',
                            '@type' => 'highlight',
                            'identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                            'title' => 'Awesome'
                        ],
                        [
                            '@type' => 'highlight',
                            'identifier' => '6d1460a7-c8ca-4fd9-89a2-3991a0766b14',
                            'title' => 'Amaze'
                        ]
                    ],
                    'books' => [ [] ] // don't create a remove command for inline_mode list
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'edit', 'command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('command');
        $this->assertInstanceOf(ModifyAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand',
                'values' => [],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'embedded_entity_commands' => [
                    [
                        '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\ModifyEmbeddedEntityCommand',
                        'values' => [],
                        'position' => 0,
                        'embedded_entity_type' => 'highlight',
                        'parent_attribute_name' => 'products',
                        'embedded_entity_commands' => [],
                        'uuid' => $command->getEmbeddedEntityCommands()->getFirst()->getUuid(),
                        'embedded_entity_identifier' => '6d1460a7-c8ca-4fd9-89a2-3991a0766b14',
                        'metadata' => []
                    ],
                    [
                        '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\RemoveEmbeddedEntity\RemoveEmbeddedEntityCommand',
                        'embedded_entity_type' => 'highlight',
                        'parent_attribute_name' => 'products',
                        'embedded_entity_commands' => [],
                        'uuid' => $command->getEmbeddedEntityCommands()->getLast()->getUuid(),
                        'embedded_entity_identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                        'metadata' => []
                    ]
                ],
                'uuid' => $command->getUuid(),
                'metadata' => [],
                'known_revision' => 2
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithEntityListActions()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_modified');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'edit' => [
                    'identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                    'revision' => 2,
                    'firstname' => 'Mark',
                    'lastname' => 'Hunt',
                    'email' => 'honeybee.user@test.com',
                    'products' => [
                        [
                            '__action' => '__move-down',
                            '@type' => 'highlight',
                            'identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                            'title' => 'Awesome'
                        ],
                        [
                            '__action' => '__duplicate',
                            '@type' => 'highlight',
                            'identifier' => '6d1460a7-c8ca-4fd9-89a2-3991a0766b14',
                            'title' => 'Amaze'
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'edit', 'command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('command');
        $embedded_commands = $command->getEmbeddedEntityCommands();
        $this->assertInstanceOf(ModifyAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ModifyAuthor\ModifyAuthorCommand',
                'values' => [],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'aggregate_root_identifier' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'embedded_entity_commands' => [
                    [
                        '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\ModifyEmbeddedEntityCommand',
                        'values' => [],
                        'position' => 0,
                        'embedded_entity_type' => 'highlight',
                        'parent_attribute_name' => 'products',
                        'embedded_entity_commands' => [],
                        'uuid' => $embedded_commands[0]->getUuid(),
                        'embedded_entity_identifier' => '6d1460a7-c8ca-4fd9-89a2-3991a0766b14',
                        'metadata' => []
                    ],
                    [
                        '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\ModifyEmbeddedEntityCommand',
                        'values' => [],
                        'position' => 1,
                        'embedded_entity_type' => 'highlight',
                        'parent_attribute_name' => 'products',
                        'embedded_entity_commands' => [],
                        'uuid' => $embedded_commands[1]->getUuid(),
                        'embedded_entity_identifier' => 'e8d1f394-4148-49de-aa5f-3e922bde11ca',
                        'metadata' => []
                    ],
                    [
                        '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\AddEmbeddedEntityCommand',
                        'values' => [
                            'title' => 'Amaze'
                        ],
                        'position' => 2,
                        'embedded_entity_type' => 'highlight',
                        'parent_attribute_name' => 'products',
                        'embedded_entity_commands' => [],
                        'uuid' => $embedded_commands[2]->getUuid(),
                        'metadata' => []
                    ]
                ],
                'uuid' => $command->getUuid(),
                'metadata' => [],
                'known_revision' => 2
            ],
            $command->toArray()
        );
    }
}
