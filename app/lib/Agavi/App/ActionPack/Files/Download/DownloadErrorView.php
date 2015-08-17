<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Files\Download;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class DownloadErrorView extends View
{
    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode(404);
    }
}
