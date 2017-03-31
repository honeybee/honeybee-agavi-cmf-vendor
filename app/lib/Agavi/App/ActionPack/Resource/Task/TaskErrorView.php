<?php

namespace Honeygavi\Agavi\App\ActionPack\Resource\Task;

use Honeygavi\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class TaskErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setContent(
            json_encode(
                array(
                    'state' => 'ok',
                    'messages' => $this->getAttribute('errors')
                )
            )
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliError(
            'Errors:' . PHP_EOL . implode(PHP_EOL, $this->getAttribute('errors', array()))
        );
    }
}
