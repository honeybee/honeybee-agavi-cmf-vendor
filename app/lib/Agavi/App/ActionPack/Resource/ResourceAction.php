<?php

namespace Honeygavi\Agavi\App\ActionPack\Resource;

use AgaviRequestDataHolder;
use Honeygavi\Agavi\App\Base\Action;

class ResourceAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $resource = $request_data->getParameter('resource');

        $this->setAttribute('resource_type', $resource->getType());
        $this->setAttribute('resource', $resource);
        $this->setAttribute('view_scope', $this->getScopeKey());

        return 'Success';
    }
}
