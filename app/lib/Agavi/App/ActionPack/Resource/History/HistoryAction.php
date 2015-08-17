<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\History;

use AgaviRequestDataHolder;
use Honeybee\Model\Event\AggregateRootEventList;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

class HistoryAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $data_access_service = $this->getServiceLocator()->getDataAccessService();
        $query_service_map = $data_access_service->getQueryServiceMap();
        $query_service = $query_service_map->getByProjectionType($this->getProjectionType());
        $resource = $request_data->getParameter('resource');
        $query_result = $query_service->findEventsByIdentifier($resource->getIdentifier());
        $domain_events = new AggregateRootEventList($query_result->getResults());

        $this->setAttribute('resource', $resource);
        $this->setAttribute('domain_events', $domain_events);

        return 'Success';
    }
}
