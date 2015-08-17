<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource;

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use AgaviRequestDataHolder;

class ResourceAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $resource_type = $this->getProjectionType();
        $resource = $request_data->getParameter('resource');

        $this->setAttribute('resource_type', $resource_type);
        $this->setAttribute('resource', $resource);
        $this->setAttribute('view_scope', $this->getScopeKey());

        return 'Success';
    }
}
