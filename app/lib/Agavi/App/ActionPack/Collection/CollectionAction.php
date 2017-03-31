<?php

namespace Honeygavi\Agavi\App\ActionPack\Collection;

use AgaviRequestDataHolder;
use Honeygavi\Agavi\App\Base\Action;
use Honeygavi\Agavi\Validator\DisplayModeValidator;
use Honeygavi\Ui\ResourceCollection;

class CollectionAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $display_mode = $this->getDisplayMode($request_data);
        $query_result = $this->query($request_data->getParameter('list_config')->asQuery());

        $this->setAttribute('resource_type', $this->getProjectionType());
        $this->setAttribute('resource_collection', new ResourceCollection($query_result->getResults()));
        $this->setAttribute('number_of_results', $query_result->getTotalCount());
        $this->setAttribute('display_mode', $display_mode);
        $this->setAttribute('activities', $this->getActivities());
        $this->setAttribute('view_scope', $this->getScopeKey());

        return ucfirst($display_mode) . 'Success';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('command', $this->dispatchCommand($request_data->getParameter('command')));
        $this->setAttribute('resource_type', $this->getProjectionType());

        return $this->executeRead($request_data);
    }

    public function handleError(AgaviRequestDataHolder $request_data)
    {
        $view = parent::handleError($request_data);
        $display_mode = $this->getDisplayMode($request_data);

        $this->setAttribute('resource_type', $this->getProjectionType());

        return ucfirst($display_mode) . $view;
    }

    protected function getDisplayMode(AgaviRequestDataHolder $request_data)
    {
        return $request_data->getParameter('as', DisplayModeValidator::DISPLAY_MODE_TABLE);
    }
}
