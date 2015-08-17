<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Create;

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use AgaviRequestDataHolder;

class CreateAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $resource_type = $this->getProjectionType();
        $this->setAttribute('resource', $request_data->getParameter('resource', $resource_type->createEntity()));
        $this->setAttribute('view_scope', $this->getScopeKey());

        return 'Input';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('resource_type', $this->getProjectionType());
        return 'Success';
    }

    public function handleWriteError(AgaviRequestDataHolder $request_data)
    {
        parent::handleError($request_data);

        return $this->executeRead($request_data);
    }
}
