<?php

namespace Honeybee\Tests\Flow\Honeybee\SystemAccount\User\Collection;

use AgaviWebResponse;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Query\QueryInterface;
use Honeybee\Infrastructure\DataAccess\Query\ProjectionQueryServiceInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Tests\Mock\HoneybeeAgaviFlowTestCase;
use Mockery;

class CollectionActionFlowTest extends HoneybeeAgaviFlowTestCase
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
     * @agaviRequestMethod read
     * @agaviRoutingInput /en_US/honeybee-system_account-user/collection
     */
    public function testExecuteRead()
    {
        $service_locator = $this->getContext()->getServiceLocator();

        $test_pr_user_type = $service_locator->getProjectionTypeMap()->getItem('honeybee.system_account.user::projection.standard');
        $mock_user_projection_1 = $test_pr_user_type->createEntity([
            'identifier' => 'honeybee.system_account.user-8e56c666-00b4-4d72-9422-a55e2548e0e5-de_DE-1',
            'workflow_state' => 'inactive'
        ]);
        $mock_user_projection_2 = $test_pr_user_type->createEntity([
            'identifier' => 'honeybee.system_account.user-351cd4f5-204c-4e20-854c-fd12f8b7e37c-de_DE-1',
            'workflow_state' => 'inactive'
        ]);

        $expected_query = [
            '@type' =>  'Honeybee\Infrastructure\DataAccess\Query\CriteriaQuery',
            'search_criteria_list' => [],
            'filter_criteria_list' => [],
            'sort_criteria_list' => [
                [
                    '@type' => 'Honeybee\Infrastructure\DataAccess\Query\SortCriteria',
                    'attribute_path' => 'modified_at',
                    'direction' => 'asc'
                ]
            ],
            'offset' => 0,
            'limit' => 50
        ];

        $this->mock_query_service
            ->shouldReceive('find')
            ->with(Mockery::on(function (QueryInterface $query) use ($expected_query) {
                $this->assertEquals($expected_query, $query->toArray());
                return true;
            }), null)
            ->andReturn(new FinderResult([ $mock_user_projection_1, $mock_user_projection_2 ], 2));

        $this->dispatch();

        $this->assertInstanceOf(AgaviWebResponse::CLASS, $this->getResponse());
        $this->assertEquals('200', $this->getResponse()->getHttpStatusCode());
        $this->assertTagCount('ul[class="hb-itemlist__items"] > li', 2);
        $this->assertTagExists('li[data-hb-item-identifier="honeybee.system_account.user-8e56c666-00b4-4d72-9422-a55e2548e0e5-de_DE-1"]');
        $this->assertTagExists('li[data-hb-item-identifier="honeybee.system_account.user-351cd4f5-204c-4e20-854c-fd12f8b7e37c-de_DE-1"]');
    }
}
