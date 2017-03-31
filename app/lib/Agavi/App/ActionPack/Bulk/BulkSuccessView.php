<?php

namespace Honeygavi\Agavi\App\ActionPack\Bulk;

use Honeygavi\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class BulkSuccessView extends View
{
    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return json_encode($this->getAttribute('report_data'));
    }
}
