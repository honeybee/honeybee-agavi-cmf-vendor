<?php

namespace Honeygavi\App\ActionPack\Suggestions;

use AgaviRequestDataHolder;
use Honeygavi\App\Base\Action;

class SuggestionsAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $list_config = $request_data->getParameter('list_config');
        $resource_type = $this->getProjectionType();

        $data_access_service = $this->getServiceLocator()->getDataAccessService();
        $query_service_map = $data_access_service->getQueryServiceMap();
        $query_service = $query_service_map->getByProjectionType($this->getProjectionType());
        $query_result = $query_service->find($list_config->asQuery());

        $this->setAttribute('display_fields', $request_data->getParameter('display_fields'));
        $this->setAttribute('resource_type', $resource_type);
        $this->setAttribute('query_result', $query_result);
        $this->setAttribute('view_scope', $this->getScopeKey());

        return 'Success';
    }
}
