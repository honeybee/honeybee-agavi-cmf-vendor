<?php

namespace Honeygavi\App\ActionPack\Files\Download;

use Honeygavi\App\Base\View;
use AgaviRequestDataHolder;

class DownloadErrorView extends View
{
    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode(404);
    }
}
