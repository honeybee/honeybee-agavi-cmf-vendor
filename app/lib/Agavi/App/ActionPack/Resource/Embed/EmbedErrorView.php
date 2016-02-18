<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Embed;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class EmbedErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $error_message = 'Cannot return a valid embed cause of errors.' . PHP_EOL . PHP_EOL;
        $validation_errors = $this->getErrorMessages();
        if (!empty($validation_errors)) {
            $error_message .= implode(PHP_EOL, $validation_errors) . PHP_EOL;
        }

        $this->setAttribute('error_message', $error_message);
    }
}
