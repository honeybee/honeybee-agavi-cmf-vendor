<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Create;

use Honeybee\Ui\Renderer\Html\Attribute\ValueRenderer;
use Honeybee\Ui\Renderer\RendererFactory;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class CreateSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        return $this->forwardToCollection($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return $this->forwardToCollection($request_data);
    }

    protected function forwardToCollection(AgaviRequestDataHolder $request_data)
    {
        $resource_type = $this->getAttribute('resource_type');
        return $this->createForwardContainer(
            sprintf('%s_%s', $resource_type->getVendor(), $resource_type->getPackage()),
            sprintf('%s.Collection', $resource_type->getName()),
            [ '__command' => $request_data->getParameter('__command') ]
        );
    }
}
