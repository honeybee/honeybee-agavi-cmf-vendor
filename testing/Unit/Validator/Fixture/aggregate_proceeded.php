<?php

use Honeygavi\Tests\Fixture\BookSchema\Task\CreateAuthor\AuthorCreatedEvent;
use Honeygavi\Tests\Fixture\BookSchema\Task\ModifyAuthor\AuthorModifiedEvent;
use Honeygavi\Tests\Fixture\BookSchema\Task\ProceedAuthorWorkflow\AuthorWorkflowProceededEvent;

// @codingStandardsIgnoreStart
return [
    new AuthorCreatedEvent([
        'data' => [
            '@type' => 'Honeygavi\Tests\Fixture\BookSchema\Task\CreateAuthor\AuthorCreatedEvent',
            'identifier' => 'honeybee_cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
            'uuid' => '63d0d3f0-251e-4a17-947a-dd3987e5a9df',
            'language' => 'de_DE',
            'version' => 1,
            'workflow_state' => 'inactive',
            'firstname' => 'Brock',
            'lastname' => 'Lesnar',
            'email' => 'honeybee.user@test.com',
            'blurb' => 'the grinch',
            'token' => '7734ad2c6332fd0503afb3213c817391b93cb078',
            'tags' => [],
            'images' => [
                [
                    'location' => 'honeybee/honeybee_cmf.test_fixtures.author/images/149/0322200b-8ea2-40ac-b395-8fcf1b9ec444.jpg',
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
            ]
        ],
        'aggregate_root_identifier' => 'honeybee_cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
        'aggregate_root_type' => 'honeybee_cmf.test_fixtures.author',
        'embedded_entity_events' => [],
        'seq_number' => 1,
        'uuid' => '0a49d63f-664d-49c5-99da-c8b32df7cd01',
        'iso_date' => '2016-06-21T08:39:39.837732+00:00',
        'metadata' => [
            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
            'role' => 'full-privileged'
        ]
    ]),
    new AuthorModifiedEvent([
        '@type' => 'Honeygavi\Tests\Fixture\BookSchema\Task\ModifyAuthor\AuthorModifiedEvent',
        'data' => [
            'firstname' => 'Mark',
            'lastname' => 'Hunt',
        ],
        'aggregate_root_identifier' => 'honeybee_cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
        'aggregate_root_type' => 'honeybee_cmf.test_fixtures.author',
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
            ]
        ],
        'seq_number' => 2,
        'uuid' => '7cc9d8d9-860c-49cd-961d-a9d15e214abd',
        'iso_date' => '2016-06-21T09:40:40.123456+00:00',
        'metadata' => [
            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
            'role' => 'full-privileged'
        ]
    ]),
    new AuthorWorkflowProceededEvent([
        '@type' => 'Honeygavi\Tests\Fixture\BookSchema\Task\ProceedAuthorWorkflow\AuthorWorkflowProceededEvent',
        'data' => [
            'workflow_state' => 'active'
        ],
        'aggregate_root_identifier' => 'honeybee_cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
        'aggregate_root_type' => 'honeybee_cmf.test_fixtures.author',
        'embedded_entity_events' => [],
        'seq_number' => 3,
        'uuid' => '2523c4b4-d2aa-4458-bd34-11a71d3adaba',
        'iso_date' => '2016-06-21T09:45:40.123456+00:00',
        'metadata' => [
            'user' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
            'role' => 'full-privileged'
        ]
    ])
];
// @codingStandardsIgnoreEnd
