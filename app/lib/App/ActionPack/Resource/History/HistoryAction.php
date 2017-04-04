<?php

namespace Honeygavi\App\ActionPack\Resource\History;

use AgaviRequestDataHolder;
use Honeybee\Model\Event\AggregateRootEventList;
use Honeygavi\App\Base\Action;

class HistoryAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $data_access_service = $this->getServiceLocator()->getDataAccessService();
        $query_service_map = $data_access_service->getQueryServiceMap();
        $domain_events_query_service = $query_service_map->getItem('honeybee::domain_event::query_service');
        $resource = $request_data->getParameter('resource');
        $query_result = $domain_events_query_service->findEventsByIdentifier($resource->getIdentifier());
        $domain_events = new AggregateRootEventList($query_result->getResults());

        $this->setAttribute('resource', $resource);
        $this->setAttribute('domain_events', $domain_events);

        return 'Success';
    }
}
