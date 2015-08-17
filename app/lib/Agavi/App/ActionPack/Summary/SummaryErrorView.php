<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Summary;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class SummaryErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
    }
}
