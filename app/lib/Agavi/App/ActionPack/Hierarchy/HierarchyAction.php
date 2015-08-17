<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Hierarchy;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\FrameworkBinding\Agavi\Validator\DisplayModeValidator;
use Honeybee\Projection\ProjectionCollection;

class HierarchyAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $query_result = $this->query($this->getSearchSpec($request_data));

        $this->setAttribute('resource_type', $this->getProjectionType());
        $this->setAttribute('resource_collection', new ProjectionCollection($query_result->getResults()));
        $this->setAttribute('number_of_results', $query_result->getTotalCount());
        $this->setAttribute('activities', $this->getActivities());
        $this->setAttribute('view_scope', $this->getScopeKey());

        return 'Success';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('command', $this->dispatchCommand($request_data->getParameter('command')));
        $this->setAttribute('resource_type', $this->getProjectionType());

        return 'Success';
    }

    protected function getSearchSpec(AgaviRequestDataHolder $request_data)
    {
        $list_config = $request_data->getParameter('list_config');
        $parent_node = $request_data->getParameter('parent_node', false);
        $query = $list_config->asQuery();

         /**
          * @todo merge hierarchy state into query
          */
        $search_spec = [];
        if ($parent_node) {
            $parent_filter = [ 'parent_node_id' => $parent_node->getIdentifier() ];
        } else {
            $parent_filter = [ 'parent_node_id' => '__empty' ];
        }
        if (array_key_exists('filter', $search_spec)) {
            $search_spec['filter'] = array_merge($search_spec['filter'], $parent_filter);
        } else {
            $search_spec['filter'] = $parent_filter;
        }

        return $query;
    }
}
