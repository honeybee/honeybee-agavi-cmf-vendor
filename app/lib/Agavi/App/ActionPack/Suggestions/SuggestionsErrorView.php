<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Suggestions;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class SuggestionsErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $parameters)
    {
        var_dump($this->getAttribute('errors'));
        exit;
    }

    public function executeJson(AgaviRequestDataHolder $parameters)
    {
        $this->getResponse()->setContent(
            json_encode(
                [
                    'state' => 'error',
                    'errors' => $this->getAttribute('errors'),
                    'data' => []
                ]
            )
        );
    }
}
