<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Summary;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class SummarySuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $module = $this->getAttribute('module');
        echo "Welcome to the " . $module->getName() . ' module.' . PHP_EOL;
    }
}
