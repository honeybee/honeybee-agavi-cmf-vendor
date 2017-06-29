<?php

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Model\Aggregate\AggregateRootTypeInterface;
use Honeygavi\App\Base\Action;

class Honeybee_Core_Util_ReplayEventsAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $aggregate_root_type = $request_data->getParameter('type');
        $channel = $request_data->getParameter('channel');
        $arid = $request_data->getParameter('identifier');

        try {
            if ($arid) {
                $distributed_events = $this->replayEventsOf($arid, $aggregate_root_type, $channel);
            } else {
                $distributed_events = $this->replayAllEventsFor($aggregate_root_type, $channel);
            }
        } catch (RuntimeError $err) {
            $this->setAttribute('distributed_events', 0);
            $this->setAttribute('errors', [$err->getMessage()]);
            return 'Error';
        }

        $this->setAttribute('distributed_events', $distributed_events);

        return 'Success';
    }

    protected function replayEventsOf(string $arid, AggregateRootTypeInterface $aggregate_root_type, string $channel)
    {
        $distributed_events = [];

        $service_locator = $this->getContext()->getServiceLocator();
        $event_bus = $service_locator->getEventBus();
        $data_access_service = $service_locator->getDataAccessService();

        $reader_key = sprintf('%s::event_stream::event_source::reader', $aggregate_root_type->getPrefix());
        $storage_reader = $data_access_service->getStorageReader($reader_key);

        $event_stream = $storage_reader->read($arid);
        if (!$event_stream || $event_stream->getEvents()->isEmpty()) {
            throw new RuntimeError('No events found for "'.$arid.'" via "'.$reader_key.'"');
        }

        foreach ($event_stream->getEvents() as $event) {
            if (!isset($distributed_events[$event->getType()])) {
                $distributed_events[$event->getType()] = 0;
            }
            $distributed_events[$event->getType()]++;

            $event_bus->distribute($channel, $event);
        }

        return $distributed_events;
    }

    protected function replayAllEventsFor(AggregateRootTypeInterface $aggregate_root_type, string $channel)
    {
        $distributed_events = [];

        $event_bus = $this->getContext()->getServiceLocator()->getEventBus();

        foreach ($this->getChronologicEventIterator($aggregate_root_type) as $event) {
            if (!isset($distributed_events[$event->getType()])) {
                $distributed_events[$event->getType()] = 0;
            }
            $distributed_events[$event->getType()]++;

            $event_bus->distribute($channel, $event);
        }

        return $distributed_events;
    }

    protected function getChronologicEventIterator(AggregateRootTypeInterface $aggregate_root_type)
    {
        $service_locator = $this->getContext()->getServiceLocator();
        $data_access_service = $service_locator->getDataAccessService();
        $reader_key = sprintf('%s::domain_event::event_source::reader', $aggregate_root_type->getPrefix());

        return $data_access_service->getStorageReader($reader_key);
    }
}
