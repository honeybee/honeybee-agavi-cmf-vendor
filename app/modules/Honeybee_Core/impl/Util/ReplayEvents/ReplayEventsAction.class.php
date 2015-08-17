<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\FrameworkBinding\Agavi\Filter\ResourcePacker;
use Honeybee\Model\Aggregate\AggregateRootTypeInterface;

class Honeybee_Core_Util_ReplayEventsAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $aggregate_root_type = $request_data->getParameter('type');
        $channel_name = $request_data->getParameter('channel');

        $event_bus = $this->getContext()->getServiceLocator()->getEventBus();
        $distributed_events = [];

        foreach ($this->getChronologicEventIterator($aggregate_root_type) as $event) {
            if (!isset($distributed_events[$event->getType()])) {
                $distributed_events[$event->getType()] = 0;
            }
            $distributed_events[$event->getType()]++;

            $event_bus->distribute($channel_name, $event);
        }

        $this->setAttribute('distributed_events', $distributed_events);

        return 'Success';
    }

    protected function getChronologicEventIterator(AggregateRootTypeInterface $aggregate_root_type)
    {
        $service_locator = $this->getContext()->getServiceLocator();
        $data_access_service = $service_locator->getDataAccessService();
        $reader_key = sprintf('%s::domain_event::event_source::reader', $aggregate_root_type->getPrefix());

        return $data_access_service->getStorageReader($reader_key);
    }
}
