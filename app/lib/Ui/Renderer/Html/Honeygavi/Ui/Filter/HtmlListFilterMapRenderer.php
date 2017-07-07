<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Filter\ListFilter;
use Honeygavi\Ui\Filter\ListFilterMap;
use Honeygavi\Ui\Renderer\Renderer;

class HtmlListFilterMapRenderer extends Renderer
{
    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof ListFilterMap) {
            throw new RuntimeError('Payload "subject" must be an instance of: ' . ListFilterMap::CLASS);
        }
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['rendered_list_filters'] = $this->renderListFilters();

        return $params;
    }

    protected function renderListFilters()
    {
        $view_scope = $this->getOption('view_scope', 'default.list_filter_map.view_scope');
        $rendered_filters = [];

        foreach ($this->getPayload('subject') as $list_filter) {
            $renderer_config = $this->view_config_service->getRendererConfig(
                $view_scope,
                $this->output_format,
                $list_filter
            );
            $render_settings = new Settings([
                'view_scope' => $view_scope
            ]);

            $rendered_filters[$list_filter->getName()] = $this->renderer_service->renderSubject(
                $list_filter,
                $this->output_format,
                $renderer_config,
                [ 'resource' => $this->getPayload('resource') ],
                $render_settings
            );
        }

        return $rendered_filters;
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/list-filters.twig';
    }
}
