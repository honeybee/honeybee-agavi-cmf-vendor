<?php

namespace Honeygavi\Agavi\App\ActionPack\Summary;

use Honeygavi\Agavi\App\Base\Action;
use AgaviRequestDataHolder;

class SummaryAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('aggregate_root_type', $this->getProjectionType());

        return 'Success';
    }
}
