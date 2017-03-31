<?php

namespace Honeygavi\Tests\Flow\Honeybee\SystemAccount\User\Resource\Modify;

use AgaviWebResponse;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Query\ProjectionQueryServiceInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeygavi\Tests\Mock\HoneybeeAgaviFlowTestCase;
use Mockery;

class ModifyActionFlowTest extends HoneybeeAgaviFlowTestCase
{
    protected $mock_query_service;

    public function setUp()
    {
        $service_locator = $this->getContext()->getServiceLocator();

        $this->mock_query_service = Mockery::mock(ProjectionQueryServiceInterface::CLASS);
        $mock_query_service_map = new QueryServiceMap([
            'honeybee.system_account.user::projection.standard::query_service' => $this->mock_query_service
        ]);

        $service_locator->prepareService(
            'honeybee.infrastructure.data_access_service',
            [ ':query_service_map' => $mock_query_service_map ]
        );
    }

    /**
     * @codingStandardIgnoreStart
     * @agaviRequestMethod read
     * @agaviRoutingInput /en_US/honeybee-system_account-user/honeybee.system_account.user-8e56c666-00b4-4d72-9422-a55e2548e0e5-de_DE-1/tasks/edit
     * @codingStandardIgnoreEnd
     */
    public function testExecuteRead()
    {
        $service_locator = $this->getContext()->getServiceLocator();

        $test_pr_user_type = $service_locator->getProjectionTypeMap()
            ->getItem('honeybee.system_account.user::projection.standard');
        $mock_user_projection = $test_pr_user_type->createEntity([
            'identifier' => 'honeybee.system_account.user-8e56c666-00b4-4d72-9422-a55e2548e0e5-de_DE-1',
            'workflow_state' => 'inactive'
        ]);

        $this->mock_query_service
            ->shouldReceive('findByIdentifier')
            ->with('honeybee.system_account.user-8e56c666-00b4-4d72-9422-a55e2548e0e5-de_DE-1')
            ->andReturn(new FinderResult([ $mock_user_projection ], 1));

        $this->dispatch();

        $this->assertInstanceOf(AgaviWebResponse::CLASS, $this->getResponse());
        $this->assertEquals('200', $this->getResponse()->getHttpStatusCode());
    }

    /**
     * @codingStandardIgnoreStart
     * @agaviRequestMethod read
     * @agaviRoutingInput /en_US/honeybee-system_account-user/honeybee.system_account.user-12345678-1234-4d72-9422-a55e2548e0e5-de_DE-1/tasks/edit
     * @codingStandardIgnoreEnd
     */
    public function testExecuteReadNotFound()
    {
        $this->mock_query_service
            ->shouldReceive('findByIdentifier')
            ->with('honeybee.system_account.user-12345678-1234-4d72-9422-a55e2548e0e5-de_DE-1')
            ->andReturn(new FinderResult([], 0));

        $this->dispatch();

        $this->assertInstanceOf(AgaviWebResponse::CLASS, $this->getResponse());
        // @todo following assertion should a 404 response
        $this->markTestIncomplete();
        $this->assertEquals('404', $this->getResponse()->getHttpStatusCode());
    }
}
