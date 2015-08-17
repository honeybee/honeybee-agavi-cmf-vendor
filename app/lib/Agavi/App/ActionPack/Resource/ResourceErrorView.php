<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class ResourceErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        // @todo set other response code, than 200, because things went wrong
        $this->getResponse()->setContent(
            json_encode($this->getAttribute('errors', array()))
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $error_message = "Processing error in " . __METHOD__;

        if ($this->hasAttribute('message')) {
            $error_message .= ' Message: ' . $this->getAttribute('message', '');
        }

        if ($this->hasAttribute('errors')) {
            $error_message .= PHP_EOL . PHP_EOL . "Resources: " . PHP_EOL;
            foreach ($this->getAttribute('errors') as $error) {
                $error_message .= "-$error" . PHP_EOL;
            }
        }

        return $this->cliError($error_message);
    }
}
