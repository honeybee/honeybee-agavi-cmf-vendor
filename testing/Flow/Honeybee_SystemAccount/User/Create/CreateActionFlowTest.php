<?php

namespace Honeybee\Tests\Flow\Honeybee\SystemAccount\User\Create;

use AgaviWebResponse;
use GuzzleHttp\Psr7\Response;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorInterface;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceInterface;
use Honeybee\Infrastructure\DataAccess\Connector\ConnectorMap;
use Honeybee\Tests\Mock\HoneybeeAgaviFlowTestCase;
use Mockery;
use Psr\Http\Message\RequestInterface;

class CreateActionFlowTest extends HoneybeeAgaviFlowTestCase
{
    protected $mock_query_service;

    protected $mock_es_connector;

    protected $mock_vs_connector;

    public function setUp()
    {
        $service_locator = $this->getContext()->getServiceLocator();

        $this->mock_query_service = Mockery::mock(QueryServiceInterface::CLASS);
        $mock_query_service_map = new QueryServiceMap([
            'honeybee.system_account.user::query_service' => $this->mock_query_service
        ]);

        $this->mock_es_connector = Mockery::mock(ConnectorInterface::CLASS);
        $this->mock_vs_connector = Mockery::mock(ConnectorInterface::CLASS);
        $connector_map = $service_locator->getInjector()->make(ConnectorMap::CLASS);
        $connector_map->setItem('Honeybee_SystemAccount.EventSource', $this->mock_es_connector);
        $connector_map->setItem('Default.ViewStore', $this->mock_vs_connector);

        $service_locator->prepareService(
            'honeybee.infrastructure.data_access_service',
            [  ':query_service_map' => $mock_query_service_map ]
        );
    }

    /**
     * @agaviRequestMethod read
     * @agaviRoutingInput /en_US/honeybee-system_account-user/create
     */
    public function testExecuteRead()
    {
        $this->dispatch();

        $this->assertInstanceOf(AgaviWebResponse::CLASS, $this->getResponse());
        $this->assertEquals('200', $this->getResponse()->getHttpStatusCode());
        $this->assertTagExists('form[action="http://testing.honeybee.com/index.php/en_US/honeybee-system_account-user/create"]');
        // @todo more tag assertions
    }

    /**
     * CreateAction::executeWrite forwards to CollectionAction::executeWrite and then returns response
     * via Collection::executeRead
     *
     * @agaviRequestMethod write
     * @agaviRoutingInput /en_US/honeybee-system_account-user/create
     */
    public function testExecuteWrite()
    {
        // setup mock clients
        $es_client = Mockery::mock(GuzzleHttp\Client::CLASS);
        $vs_client = Mockery::mock(Elasticsearch\Client::CLASS);

        // setup event source writer expectations
        $es_client->shouldReceive('send')->once()->with(Mockery::on(
            function (RequestInterface $request) {
                // set global dynamic data from request to inject into fixtures for assertions
                $this->setFixtureData($request);
                $this->assertEventSourceSend($request, '01_es_send.php');
                return true;
            }
        ))->andReturn(new Response(200, [ 'Content-Type' => 'application/json' ], '{"ok":true, "rev":1}'));

        // setup view store writer expectations
        $vs_client->shouldReceive('index')->once()->with(Mockery::on(
            function (array $event) {
                $expected = $this->loadFixture('01_vs_index_event.php');
                $this->assertEquals($expected, $event);
                return true;
            }
        ));

        // projection updater expectations
        $vs_client->shouldReceive('index')->once()->with(Mockery::on(
            function (array $doc) {
                $expected = $this->loadFixture('01_vs_index_doc.php');
                $this->assertEquals($expected, $doc);
                return true;
            }
        ));

        // connection expectations
        $this->mock_es_connector->shouldReceive('getConnection')->once()->andReturn($es_client);
        $this->mock_es_connector->shouldReceive('getConfig')->andReturn(new ArrayConfig([ 'database' => 'test-db' ]));
        $this->mock_vs_connector->shouldReceive('getConnection')->times(2)->andReturn($vs_client);
        $this->mock_vs_connector->shouldReceive('getConfig')->andReturn(
            new ArrayConfig([ 'index' => 'test-index', 'type' => 'test-type' ])
        );

        // action redirection expectations
        // @todo why reload then redirect?
        $service_locator = $this->getContext()->getServiceLocator();
        $test_pr_user_type = $service_locator->getProjectionTypeMap()->getItem('honeybee.system_account.user');
        $mock_user_projection = $test_pr_user_type->createEntity([
            'identifier' => 'honeybee.system_account.user-8e56c666-00b4-4d72-9422-a55e2548e0e5-de_DE-1',
            'workflow_state' => 'inactive'
        ]);
        $this->mock_query_service
            ->shouldReceive('find')
            ->andReturn(new FinderResult([ $mock_user_projection ], 1));

        // execute this mofo
        $this->dispatch([
            'create_user' => [
                'username' => 'test user',
                'email' => 'honeybee.user@test.com',
                'role' => 'administrator',
                'firstname' => 'Brock',
                'lastname' => 'Lesnar'
            ]
        ]);

        $this->assertInstanceOf(AgaviWebResponse::CLASS, $this->getResponse());
        $this->assertEquals('200', $this->getResponse()->getHttpStatusCode());
        // @todo response ending at redirect
//         $this->assertTagExists('form[action="http://testing.honeybee.com/index.php/en_US/honeybee-system_account-user/create"]');
    }

    //-------------------------------helpers-----------------------------------

    /*
     * the event is the first access to dynamically created data so we setup anything for fixture
     * templates here to make assertions easier.
     */
    protected function setFixtureData(RequestInterface $request)
    {
        $body = json_decode($request->getBody(), true);
        $this->fixture_data['event_uuid'] = $body['uuid'];
        $this->fixture_data['event_iso_date'] = $body['iso_date'];
        $this->fixture_data['identifier'] = $body['data']['identifier'];
        $this->fixture_data['uuid'] = $body['data']['uuid'];
        $this->fixture_data['auth_token'] = $body['data']['auth_token'];
        $this->fixture_data['token_expire_date'] = $body['data']['token_expire_date'];
    }

    protected function assertEventSourceSend(RequestInterface $request, $filename)
    {
        $request_data['method'] = $request->getMethod();
        $request_data['uri'] = (string)$request->getUri();
        $request_data['headers'] = $request->getHeaders();
        $request_data['body'] = json_decode($request->getBody(), true);
        $expected = $this->loadFixture($filename);
        $this->assertEquals($expected, $request_data);
    }

    protected function loadFixture($filename)
    {
        // bring dynamic data in scope for fixtures
        $fixture_data = $this->getFixtureData();
        return require_once(__DIR__ . '/Fixture/' . $filename);
    }
}
