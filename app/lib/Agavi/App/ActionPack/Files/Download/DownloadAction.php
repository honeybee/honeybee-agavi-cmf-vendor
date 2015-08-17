<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Files\Download;

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use AgaviRequestDataHolder;

class DownloadAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('aggregate_root_type', $this->getAggregateRootType());

        return 'Success';
    }
}
