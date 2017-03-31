<?php

namespace Honeygavi\Agavi\App\ActionPack\Files\Download;

use Honeygavi\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class DownloadErrorView extends View
{
    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode(404);
    }
}
