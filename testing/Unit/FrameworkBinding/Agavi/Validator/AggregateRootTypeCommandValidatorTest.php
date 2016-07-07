<?php

namespace Honeybee\Tests\Unit\FrameworkBinding\Agavi\Validator;

use AgaviValidationReportQuery;
use AgaviWebRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\Request\HoneybeeUploadedFile;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootTypeCommandValidator;
use Honeybee\Tests\Mock\HoneybeeAgaviUnitTestCase;
use Honeybee\Tests\Fixture\BookSchema\Task\CreateAuthor\CreateAuthorCommand;

class AggregateRootTypeCommandValidatorTest extends HoneybeeAgaviUnitTestCase
{
    protected $vm;

    protected $filesystem_service;

    public function setUp()
    {
        $this->vm = $this->getContext()->createInstanceFor('validation_manager');
        $this->vm->clear();
        $this->filesystem_service = $this->getContext()->getServiceLocator()->getFilesystemService();
        $this->filesystem_service->clear();
    }

    protected function createValidator($base = 'create_author')
    {
        return $this->vm->createValidator(
            AggregateRootTypeCommandValidator::CLASS,
            [ '__submit' ],
            [
                '' => 'Invalid command payload given.',
                'no_image' => 'Uploaded image not found.',
                'firstname.mandatory' => 'Firstname is required.',
                'firstname.min_length' => 'Firstname is too short.',
                'firstname.max_length' => 'Firstname is too long.',
                'email.mandatory' => 'Email is required.',
                'email.invalid_format' => 'Email has an invalid format.',
                'products.max_count' => 'Maximum number of products exceeded.',
                'products.highlight.title.mandatory' => 'Title is required.',
                'products.highlight.title.min_length' => 'Title is too short.'
            ],
            [
                'name' => 'invalid_task_data',
                'base' => $base,
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'command_implementor' => 'Honeybee\Tests\Fixture\BookSchema\Task\CreateAuthor\CreateAuthorCommand',
                'attribute_whitelist' => [ 'products', 'nada', 'token' ],
                'attribute_blacklist' => [
                    'token',
                    'products.highlight.ignored',
                    'products.highlight.non.existent.attr'
                ],
                'export' => '__command'
            ]
        );
    }

    public function testExecute()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'token' => 'ignored'
                ]
            ],
            AgaviWebRequestDataHolder::SOURCE_FILES => []
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'create_author', '__command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('__command');
        $this->assertInstanceOf(CreateAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\CreateAuthor\CreateAuthorCommand',
                'values' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com'
                ],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => []
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithInvalidSource()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'a',
                    'email' => 'invalidemail.com'
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(3, $query->count());
        $this->assertEquals(
            [ 'Email has an invalid format.' ],
            $query->byArgument('create_author[email]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Firstname is too short.' ],
            $query->byArgument('create_author[firstname]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithEmptySource()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [],
            AgaviWebRequestDataHolder::SOURCE_FILES => []
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(3, $query->count());
        $this->assertEquals(
            [ 'Firstname is required.' ],
            $query->byArgument('create_author[firstname]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Email is required.' ],
            $query->byArgument('create_author[email]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithCommand()
    {
        $validator = $this->createValidator(null);

        $source_command = new CreateAuthorCommand([
            'values' => [
                'firstname' => 'Brock',
                'lastname' => '',
                'email' => 'honeybee.user@test.com'
            ],
            'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
            'embedded_entity_commands' => [],
            'metadata' => []
        ]);

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [ '__command' => $source_command ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::SUCCESS, $result);
        $this->assertEquals([ '__command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('__command');
        $this->assertInstanceOf(CreateAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\CreateAuthor\CreateAuthorCommand',
                'values' => [
                    'firstname' => 'Brock',
                    'lastname' => '',
                    'email' => 'honeybee.user@test.com'
                ],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => []
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithEmbeddedEntities()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'products' => [
                        [
                            '@type' => 'highlight',
                            'title' => 'Hall of Fame',
                            'ignored' => 'ignore this'
                        ]
                    ],
                    'books' => [
                        [
                            '@type' => 'book',
                            'referenced_identifier' => 'honeybee-cmf.test_fixtures.book-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
                            'title' => 'test book'
                        ]
                    ],
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'create_author', '__command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('__command');
        $this->assertInstanceOf(CreateAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\CreateAuthor\CreateAuthorCommand',
                'values' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com'
                ],
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'embedded_entity_commands' => [
                    [
                        '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\AddEmbeddedEntityCommand',
                        'values' => [
                            'title' => 'Hall of Fame'
                        ],
                        'position' => 0,
                        'embedded_entity_type' => 'highlight',
                        'parent_attribute_name' => 'products',
                        'embedded_entity_commands' => [],
                        'uuid' => $command->getEmbeddedEntityCommands()->getFirst()->getUuid(),
                        'metadata' => []
                    ],
                    [
                        '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\AddEmbeddedEntityCommand',
                        'values' => [
                            'referenced_identifier' => 'honeybee-cmf.test_fixtures.book-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
                            'title' => 'test book'
                        ],
                        'position' => 0,
                        'embedded_entity_type' => 'book',
                        'parent_attribute_name' => 'books',
                        'embedded_entity_commands' => [],
                        'uuid' => $command->getEmbeddedEntityCommands()->getLast()->getUuid(),
                        'metadata' => []
                    ]
                ],
                'uuid' => $command->getUuid(),
                'metadata' => []
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithInvalidEmbeddedEntities()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'x',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'products' => [
                        [
                            '@type' => 'highlight',
                            'title' => 'x'
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(3, $query->count());
        $this->assertEquals(
            [ 'Firstname is too short.' ],
            $query->byArgument('create_author[firstname]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Title is too short.' ],
            $query->byArgument('create_author[products][0][title]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithEmbeddedEntityMissingType()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'products' => [
                        [
                            'title' => 'Wait What'
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(2, $query->count());
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[products][0][@type]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithInvalidEmbeddedEntityType()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'products' => [
                        [
                            '@type' => 'invalid',
                            'title' => 'Who am I?'
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(2, $query->count());
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[products][0][@type]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithExcessEmbeddedEntities()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'products' => [
                        [
                            '@type' => 'highlight',
                            'title' => 'Awesome'
                        ],
                        [
                            '@type' => 'highlight',
                            'title' => 'Amazing'
                        ],
                        [
                            '@type' => 'highlight',
                            'title' => 'Wicked'
                        ]
                    ]
                ]
            ]
        ]);

        // @todo current command validator cannot easily validate embedded entity list attribute rules
        // such as max_count but we can support in future using the command builder.
        $this->markTestIncomplete();

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(2, $query->count());
        $this->assertEquals(
            [ 'Maximum number of products exceeded.' ],
            $query->byArgument('create_author[products]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithMissingEmbeddedEntityValues()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    // missing mandatory values
                    'products' => [
                        [
                            '@type' => 'highlight'
                            // missing mandatory title
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(4, $query->count());
        $this->assertEquals(
            [ 'Firstname is required.' ],
            $query->byArgument('create_author[firstname]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Email is required.' ],
            $query->byArgument('create_author[email]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Title is required.' ],
            $query->byArgument('create_author[products][0][title]')->getErrorMessages()
        );
        $this->assertEquals(
            [ 'Invalid command payload given.' ],
            $query->byArgument('create_author[__submit]')->getErrorMessages()
        );
    }

    public function testExecuteWithPreUploadedFile()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'images' => [
                        [ 'location' => '' ],
                        [
                            'location' => __DIR__ . '/Fixture/kitty.jpg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  'kitty.jpg',
                            'filesize' => '116752',
                            'mimetype' => 'image/jpeg',
                            'width' => '1200',
                            'height' => '1200'
                        ]
                    ]
                ]
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(AggregateRootTypeCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'create_author', '__command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('__command');
        $this->assertInstanceOf(CreateAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\CreateAuthor\CreateAuthorCommand',
                'values' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'images' => [
                        [
                            'location' => __DIR__ . '/Fixture/kitty.jpg',
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
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => []
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithUploadedFile()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'images' => [
                        [
                            'location' => __DIR__ . '/Fixture/kitty.jpg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  '',
                            'filesize' => '0',
                            'mimetype' => '',
                            'width' => '0',
                            'height' => '0'
                        ]
                    ]
                ]
            ],
            AgaviWebRequestDataHolder::SOURCE_FILES => [
                'create_author' => [
                    'images' => [
                        [
                            'file' => new HoneybeeUploadedFile([
                                'name' => 'kitty.jpg',
                                'type' => 'image/jpeg',
                                'size' => 116752,
                                'tmp_name' => __DIR__ . '/Fixture/kitty.jpg',
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

        $this->assertEquals(AggregateRootTypeCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'create_author', '__command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $uploaded_images = $this->filesystem_service->getTestResourceUris();
        $this->assertCount(1, $uploaded_images);
        $command = $rd->getParameter('__command');
        $this->assertInstanceOf(CreateAuthorCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\CreateAuthor\CreateAuthorCommand',
                'values' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'images' => [
                        [
                            'location' => str_replace('honeybee-cmf.test_fixtures.author.tempfiles://', '', $uploaded_images[0]),
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
                'embedded_entity_commands' => [],
                'uuid' => $command->getUuid(),
                'metadata' => []
            ],
            $command->toArray()
        );
    }

    public function testExecuteWithMissingUploadedFile()
    {
        $validator = $this->createValidator();

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'create_author' => [
                    'firstname' => 'Brock',
                    'lastname' => 'Lesnar',
                    'email' => 'honeybee.user@test.com',
                    'images' => [
                        [
                            'location' => __DIR__ . '/Fixture/kittynotfound.jpg',
                            'title' => 'Kitty',
                            'caption' => 'Meow',
                            'copyright' => '',
                            'copyright_url' => '',
                            'source' =>  '',
                            'aoi' => '',
                            'filename' =>  '',
                            'filesize' => '0',
                            'mimetype' => '',
                            'width' => '0',
                            'height' => '0'
                        ]
                    ]
                ]
            ],
            AgaviWebRequestDataHolder::SOURCE_FILES => [
                'create_author' => [
                    'images' => [
                        [
                            'file' => new HoneybeeUploadedFile([
                                'name' => 'kitty.jpg',
                                'type' => 'image/jpeg',
                                'size' => 116752,
                                'tmp_name' => __DIR__ . '/Fixture/kittynotfound.jpg',
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

        $this->assertEquals(AggregateRootTypeCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'create_author', '__command' ], $rd->getParameterNames());
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->markTestIncomplete();
        // @todo following assertion should yield a message. Currently we are using the
        // AgaviImageFileValidator which is not aware of the argument payload path
        $this->assertEquals(
            [ 'Uploaded image not found.' ],
            $query->byArgument('create_author[images][0][file]')->getErrorMessages()
        );
    }
}
