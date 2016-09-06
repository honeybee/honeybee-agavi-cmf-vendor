<?php

namespace Honeybee\Ui\Renderer\Haljson\Honeybee\Ui;

use Honeybee\Ui\Renderer\Haljson\HaljsonRenderer;
use Honeybee\Ui\ResourceCollection;
use Honeybee\Common\Error\RuntimeError;

class HaljsonResourceCollectionRenderer extends HaljsonRenderer
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
        $params = parent::getTemplateParameters();

        $resource_collection = $this->getPayload('subject');

        $view_scope = $this->getOption('view_scope', 'missing.view_scope.collection');

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
