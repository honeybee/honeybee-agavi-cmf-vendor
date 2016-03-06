<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Suggestions;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class SuggestionsSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $parameters)
    {
        var_dump($this->getAttribute('suggestions', []));
        exit;
    }

    public function executeJson(AgaviRequestDataHolder $parameters)
    {
        $this->getResponse()->setContent(
            json_encode(
                [
                    'state' => 'ok',
                    'data' => $this->getAttribute('suggestions', [])
                ]
            )
        );
    }
}
