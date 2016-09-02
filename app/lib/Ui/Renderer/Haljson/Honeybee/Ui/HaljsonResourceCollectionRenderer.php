<?php

namespace Honeybee\Ui\Renderer\Haljson\Honeybee\Ui;

use Honeybee\Ui\Renderer\EntityListRenderer;
use Honeybee\Ui\ResourceCollection;
use Honeybee\Common\Error\RuntimeError;

class HaljsonResourceCollectionRenderer extends EntityListRenderer
{
    const STATIC_TRANSLATION_PATH = 'collection';

    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof ResourceCollection) {
            throw new RuntimeError(
                sprintf('Payload "subject" must implement "%s".', ResourceCollection::CLASS)
            );
        }
    }

    protected function doRender()
    {
        $params = parent::getTemplateParameters();

        $resource_collection = $this->getPayload('subject');

        $scope = $this->getOption('view_scope', 'default.collection');

        $default_data = [
            'view_scope' => $scope, // e.g. honeybee.system_account.user.collection
        ];

        $rendered_resources = [];
        foreach ($resource_collection as $resource) {
            $renderer_config = $this->view_config_service->getRendererConfig(
                $scope,
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

        return json_encode($rendered_resources);
    }
}
