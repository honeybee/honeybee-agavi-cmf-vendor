<?php

namespace Honeygavi\Agavi\App\ActionPack\Bulk;

use Honeygavi\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class BulkErrorView extends View
{
    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return json_encode(
            array('errors' => $this->getErrorMessages())
        );
    }
}
