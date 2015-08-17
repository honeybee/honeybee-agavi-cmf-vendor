<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Suggestions;

use AgaviRequestDataHolder;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

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
        $display_attribute_names = $request_data->getParameter('display_fields');

        foreach ($display_attribute_names as $display_attribute_name) {
            if (!$resource_type->hasAttribute($display_attribute_name)) {
                throw new RuntimeError(
                    sprintf(
                        'Non existant display_field "%s" given for type %s',
                        $display_attribute_name,
                        $resource_type->getName()
                    )
                );
            }
        }

        $suggestions = [];
        foreach ($query_result->getResults() as $resource) {
            $suggestion = [ 'identifier' => $resource->getIdentifier() ];
            foreach ($display_attribute_names as $display_attribute_name) {
                $suggestion[$display_attribute_name] = $resource->getValue($display_attribute_name);
            }
            $suggestions[] = $suggestion;
        }

        $this->setAttribute('resource_type', $resource_type);
        $this->setAttribute('suggestions', $suggestions);
        $this->setAttribute('view_scope', $this->getScopeKey());

        return 'Success';
    }
}
