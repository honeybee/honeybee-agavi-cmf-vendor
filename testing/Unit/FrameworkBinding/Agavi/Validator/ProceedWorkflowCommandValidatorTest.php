<?php

namespace Honeybee\Tests\Unit\FrameworkBinding\Agavi\Validator;

use AgaviContext;
use AgaviValidationReportQuery;
use AgaviWebRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\Validator\ProceedWorkflowCommandValidator;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Model\Task\ProceedWorkflow\ProceedWorkflowCommand;
use Honeybee\Tests\Mock\HoneybeeAgaviUnitTestCase;
use Mockery;

class ProceedWorkflowCommandValidatorTest extends HoneybeeAgaviUnitTestCase
{
    protected $vm;

    protected $mock_query_service;

    // need to run in isolation for manageable mock service expectations
    protected $runTestInSeparateProcess = true;

    public function setUp()
    {
        $this->vm = $this->getContext()->createInstanceFor('validation_manager');
        $this->vm->clear();

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
        $fixture = 'aggregate_created',
        $identifier = 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1'
    ) {
        $events = require(__DIR__ . '/Fixture/' . $fixture . '.php');
        $this->mock_query_service
            ->shouldReceive('findEventsByIdentifier')
            ->once() // clear expectation after returning results once
            ->with($identifier)
            ->andReturn(new FinderResult($events, count($events)));
    }

    protected function createValidator($base = null)
    {
        return $this->vm->createValidator(
            ProceedWorkflowCommandValidator::CLASS,
            [ 'event' ],
            [
                '' => 'Invalid command payload given.',
                'conflict_detected' => 'Data has changed and conflicts with your attempt to modify it.',
                'invalid_workflow_event' => 'Workflow event is invalid.'
            ],
            [
                'name' => 'invalid_proceed_command',
                'base' => $base,
                'aggregate_root_type' => 'honeybee-cmf.test_fixtures.author',
                'identifier_arg' => 'resource',
                'command_implementor' => 'Honeybee\Tests\Fixture\BookSchema\Task\ProceedAuthorWorkflow\ProceedAuthorWorkflowCommand'
            ]
        );
    }

    public function xtestExecute()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'resource' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'event' => 'promote',
                'revision' => 1
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(ProceedWorkflowCommandValidator::SUCCESS, $result);
        $this->assertEquals([ 'resource', 'event', 'revision', 'command' ], $rd->getParameterNames());
        $this->assertCount(0, $this->vm->getReport()->getErrorMessages());
        $command = $rd->getParameter('command');
        $this->assertInstanceOf(ProceedWorkflowCommand::CLASS, $command);
        $this->assertEquals(
            [
                '@type' => 'Honeybee\Tests\Fixture\BookSchema\Task\ProceedAuthorWorkflow\ProceedAuthorWorkflowCommand',
                'current_state_name' => 'inactive',
                'event_name' => 'promote',
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

    public function testExecutWithInvalidEvent()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'resource' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'event' => 'invalid',
                'revision' => 1
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(ProceedWorkflowCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->assertEquals(
            [ 'Workflow event is invalid.' ],
            $query->byArgument('event')->getErrorMessages()
        );
    }

    public function testExecutWithMissingEvent()
    {
        $validator = $this->createValidator();
        $this->prepareHistory('aggregate_created');

        $rd = new AgaviWebRequestDataHolder([
            AgaviWebRequestDataHolder::SOURCE_PARAMETERS => [
                'resource' => 'honeybee-cmf.test_fixtures.author-63d0d3f0-251e-4a17-947a-dd3987e5a9df-de_DE-1',
                'revision' => 1
            ]
        ]);

        $result = $validator->execute($rd);

        $this->assertEquals(ProceedWorkflowCommandValidator::ERROR, $result);
        $query = new AgaviValidationReportQuery($this->vm->getReport());
        $this->assertEquals(1, $query->count());
        $this->assertEquals(
            [ 'Workflow event is invalid.' ],
            $query->byArgument('event')->getErrorMessages()
        );
    }
}
