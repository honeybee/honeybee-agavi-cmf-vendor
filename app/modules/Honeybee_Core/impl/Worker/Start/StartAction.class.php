<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Job\Worker\Worker;

class Honeybee_Core_Worker_StartAction extends Action
{
    public function execute(AgaviRequestDataHolder $request_data)
    {
        $service_locator = $this->getServiceLocator();

        $queue = $request_data->getParameter('queue');

        $worker_state = [ ':config' => new ArrayConfig([ 'queue' => $queue ]) ];

        $service_locator->createEntity(Worker::CLASS, $worker_state)->run();

        return AgaviView::NONE;
    }
}
