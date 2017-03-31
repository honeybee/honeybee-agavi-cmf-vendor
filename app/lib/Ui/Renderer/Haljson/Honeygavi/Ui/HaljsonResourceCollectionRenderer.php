<?php

namespace Honeygavi\Ui\Renderer\Haljson\Honeygavi\Ui;

use Honeygavi\Ui\Renderer\Renderer;
use Honeygavi\Ui\ResourceCollection;
use Honeybee\Common\Error\RuntimeError;

class HaljsonResourceCollectionRenderer extends Renderer
{
    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof ResourceCollection) {
            throw new RuntimeError('Payload "subject" must implement: ' . ResourceCollection::CLASS);
        }
    }

    /**
     * @return array
     */
    protected function doRender()
    {
        $resource_collection = $this->getPayload('subject');

        $view_scope = $this->getOption('view_scope', 'missing.collection.view_scope');

        $default_data = [
            'view_scope' => $view_scope,
        ];

        $rendered_resources = [];
        foreach ($resource_collection as $resource) {
            $renderer_config = $this->view_config_service->getRendererConfig(
                $view_scope,
                $this->output_format,
                $resource,
                $default_data
            );

            $rendered_resources[] = $this->renderer_service->renderSubject(
                $resource,
                $this->output_format,
                $renderer_config,
                [],
                $this->settings
            );
        }

        return $rendered_resources;
    }
}
