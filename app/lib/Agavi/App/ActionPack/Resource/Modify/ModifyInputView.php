<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\Modify;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class ModifyInputView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $resource = $this->getAttribute('resource');

        $view_scope = $this->getAttribute('view_scope', 'default.templates.modify');

        $default_ns = sprintf('modify_%s', $resource->getType()->getPrefix());
        $renderer_settings = [ 'group_parts' => [ $request_data->getParameter('data_ns', $default_ns) ] ];
        $rendered_resource = $this->renderSubject($resource, $renderer_settings);
        $this->setAttribute('rendered_resource', $rendered_resource);

        $this->setSubheaderActivities($request_data);
        $this->setPrimaryActivities($request_data);

        if ($template = $this->getCustomTemplate($resource)) {
            $this->getLayer('content')->setTemplate($template);
        }
    }
}
