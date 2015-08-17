<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Modify;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class ModifyErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $this->setAttribute('errors', $this->getErrorMessages());
    }
}
