<?php

namespace Honeygavi\App\ActionPack\Files\Upload;

use Honeygavi\App\Base\Action;
use AgaviRequestDataHolder;

class UploadAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('aggregate_root_type', $this->getAggregateRootType());

        return 'Input';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('aggregate_root_type', $this->getAggregateRootType());

        return 'Success';
    }

    public function handleError(AgaviRequestDataHolder $request_data)
    {
        parent::handleError($request_data);

        $this->setAttribute('aggregate_root_type', $this->getAggregateRootType());

        return 'Error';
    }
}
