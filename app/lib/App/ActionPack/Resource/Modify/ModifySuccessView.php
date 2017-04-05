<?php

namespace Honeygavi\App\ActionPack\Resource\Modify;

use Honeygavi\App\Base\View;
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
