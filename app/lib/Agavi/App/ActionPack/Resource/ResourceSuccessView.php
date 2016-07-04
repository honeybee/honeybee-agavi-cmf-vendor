<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class ResourceSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $resource = $this->getAttribute('resource');

        $view_config_scope = $this->getAttribute('view_config_scope', 'default.templates.modify');
        $renderer_settings = $this->getResourceRendererSettings();
        $rendered_resource = $this->renderSubject($resource, $renderer_settings);

        $routing = $this->getContext()->getRouting();
        $head_revision = $request_data->getParameter('head_revision');
        $prev_link = false;
        if ($resource->getRevision() > 1) {
            $prev_link = $routing->gen(null, [ 'revision' => $resource->getRevision() - 1 ]);
        }
        $next_link = false;
        if ($resource->getRevision() < $head_revision) {
            $next_link = $routing->gen(null, [ 'revision' => $resource->getRevision() + 1 ]);
        }

        $this->setSubheaderActivities($request_data);

        $this->setAttribute('rendered_resource', $rendered_resource);
        $this->setAttribute('prev_link', $prev_link);
        $this->setAttribute('next_link', $next_link);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $resource = $this->getAttribute('resource');

        $this->getResponse()->setContent(json_encode($resource->toArray()));
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        echo print_r($this->getAttribute('resource')->toArray(), true) . PHP_EOL;
    }

    protected function getResourceRendererSettings($default_settings = [])
    {
        return array_replace_recursive($default_settings, []);
    }
}
