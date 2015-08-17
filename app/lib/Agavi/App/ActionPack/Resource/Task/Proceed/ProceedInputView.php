<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Task\Proceed;

use Honeybee\Infrastructure\Workflow\Plugin\InteractionResult;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class ProceedInputView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $available_gates = $this->getAttribute('gates', []);

        $this->getResponse()->setContent(
            json_encode(
                array(
                    'state' => 'ok',
                    'data' => $available_gates
                )
            )
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        return PHP_EOL . "Available gates are: " . join(', ', $this->getAttribute('gates', [])) . PHP_EOL
            . "Append '.write' to the current route to run this action with write access." . PHP_EOL;
    }
}
