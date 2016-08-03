<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\History;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class HistoryErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setContent(
            json_encode($this->getAttribute('errors'))
        );
    }

    public function executeConsole(AgaviRequestDataHolder $reqeust_data)
    {
        $this->cliError(
            'Errors:' . PHP_EOL . implode(PHP_EOL, $this->getAttribute('errors', []))
        );
    }
}
