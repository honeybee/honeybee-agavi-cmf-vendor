<?php

namespace Honeygavi\App\ActionPack\Resource\Task\Proceed;

use Honeygavi\App\Base\View;
use AgaviRequestDataHolder;

class ProceedErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setContent(
            json_encode(
                [
                    'state' => 'error',
                    'errors' => $this->getAttribute('errors', [])
                ]
            )
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliError(
            'Errors:' . PHP_EOL . implode(PHP_EOL, $this->getAttribute('errors', []))
        );
    }
}
