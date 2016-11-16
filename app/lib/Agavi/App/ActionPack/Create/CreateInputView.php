<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Create;

use AgaviRequestDataHolder;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class CreateInputView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $resource = $this->getAttribute('resource');

        $view_config_scope = $this->getAttribute('view_scope');
        $entity_short_prefix = StringToolkit::asSnakeCase($resource->getType()->getName());
        $data_ns = sprintf('create_%s', $entity_short_prefix);
        $default_settings = [ 'group_parts' => [ $data_ns ] ];
        $renderer_settings = $this->getResourceRendererSettings($default_settings);
        $rendered_resource = $this->renderSubject($resource, $renderer_settings);
        $this->setAttribute('rendered_resource', $rendered_resource);

        $this->setSubheaderActivities($request_data);
        $this->setPrimaryActivities($request_data);

        $this->setAttribute('create_url', $this->routing->gen(null));

        if ($template = $this->getCustomTemplate($resource)) {
            $this->getLayer('content')->setTemplate($template);
        }
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return json_encode(__METHOD__);
    }

    public function getBreadcrumbsActivities()
    {
        $breadcrumbs_root_activities = $this->getBreadcrumbsRootActivities();

        return $breadcrumbs_root_activities;
    }

    public function getBreadcrumbsRootActivities()
    {
        $resource_type = $this->getAttribute('resource')->getType();

        return [
            $this->getServiceLocator()->getActivityService()->getActivity($resource_type->getPrefix(), 'collection')
        ];
    }

    protected function getResourceRendererSettings($default_settings = [])
    {
        return array_replace_recursive([], $default_settings);
    }
}
