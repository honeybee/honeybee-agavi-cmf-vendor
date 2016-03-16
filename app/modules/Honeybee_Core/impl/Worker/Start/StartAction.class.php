<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Job\Worker\Worker;
use Honeybee\Infrastructure\Event\Bus\Transport\JobQueueTransport;

class Honeybee_Core_Worker_StartAction extends Action
{
    public function execute(AgaviRequestDataHolder $request_data)
    {
        $service_locator = $this->getServiceLocator();

        $worker_state = [ ':config' => $this->buildWorkerConfig($request_data) ];

        $service_locator->createEntity(Worker::CLASS, $worker_state)->run();

        return AgaviView::NONE;
    }

    protected function buildWorkerConfig(AgaviRequestDataHolder $request_data)
    {
        $exchange = $request_data->getParameter('exchange', JobQueueTransport::DEFAULT_EVENT_EXCHANGE);
        $job = $request_data->getParameter('job');

        return new ArrayConfig([ 'exchange' => $exchange, 'job' => $job ]);
    }
}
