<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Modify;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class ModifySuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setRedirect(
            $this->getContext()->getRouting()->gen(null, [], [ 'relative' => false ])
        );
        return;
    }
}
