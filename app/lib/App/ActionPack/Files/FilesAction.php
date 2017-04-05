<?php

namespace Honeygavi\App\ActionPack\Files;

use Honeygavi\App\Base\Action;
use AgaviRequestDataHolder;

class FilesAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('aggregate_root_type', $this->getAggregateRootType());

        return 'Success';
    }

    public function handleError(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('aggregate_root_type', $this->getAggregateRootType());

        return 'Error';
    }
}
