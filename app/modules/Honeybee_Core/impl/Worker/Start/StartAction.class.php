<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Job\Worker\Worker;

class Honeybee_Core_Worker_StartAction extends Action
{
    const COMMAND_WORKER = 'command';

    const EVENT_WORKER = 'event';

    const DEFAULT_EVENT_EXCHANGE = 'honeybee.domain.events';

    const DEFAULT_COMMAND_EXCHANGE = 'honeybee.domain.commands';

    const GLOBAL_QUEUE = '.global';

    const BIND_ALL = '#';

    public function execute(AgaviRequestDataHolder $request_data)
    {
        $service_locator = $this->getServiceLocator();
        $connector_service = $service_locator->getConnectorService();

        $worker_state = [
            ':connector' => $connector_service->getConnector('Default.MsgQueue'),
            ':config' => $this->buildWorkerConfig($request_data)
        ];

        $service_locator->createEntity(Worker::CLASS, $worker_state)->run();

        return AgaviView::NONE;
    }

    protected function buildWorkerConfig(AgaviRequestDataHolder $request_data)
    {
        $worker_type = $request_data->getParameter('type');
        if ($worker_type === self::COMMAND_WORKER) {
            $default_exchange = self::DEFAULT_COMMAND_EXCHANGE;
            $bindings = $this->buildCommandBindings();
        } else {
            $default_exchange = self::DEFAULT_EVENT_EXCHANGE;
            $bindings = array(self::BIND_ALL);
        }

        $exchange = $request_data->getParameter('exchange', $default_exchange);
        $queue = $exchange . self::GLOBAL_QUEUE;

        return new ArrayConfig(
            [
                'exchange' => $exchange,
                'queue' => $queue,
                'bindings' => $bindings
            ]
        );
    }

    protected function buildCommandBindings()
    {
        $command_bus = $this->getServiceLocator()->getCommandBus();
        $bindings = [];

        foreach ($command_bus->getSubscriptions() as $subscription) {
            $bindings[] = $subscription->getCommandType();
        }

        return $bindings;
    }
}
