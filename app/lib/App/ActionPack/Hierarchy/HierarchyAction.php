<?php

namespace Honeygavi\App\ActionPack\Hierarchy;

use AgaviRequestDataHolder;
use Honeygavi\App\Base\Action;
use Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria;
use Honeybee\Infrastructure\DataAccess\Query\Comparison\Equals;
use Honeygavi\Ui\ResourceCollection;

class HierarchyAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $query_result = $this->query($this->getSearchSpec($request_data));

        $this->setAttribute('resource_type', $this->getProjectionType());
        $this->setAttribute('resource_collection', new ResourceCollection($query_result->getResults()));
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

        if ($parent_node) {
            $query->getFilterCriteriaList()->push(
                new AttributeCriteria('parent_node_id', new Equals($parent_node->getIdentifier()))
            );
        } else {
            $query->getFilterCriteriaList()->push(
                new AttributeCriteria('parent_node_id', new Equals('__empty'))
            );
        }

        return $query;
    }
}
