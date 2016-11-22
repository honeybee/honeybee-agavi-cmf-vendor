<?php

namespace Honeybee\Ui\Renderer;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Ui\Activity\ActivityInterface;

/**
 * Whatever is given as payload to this renderer may be used via an expression to set the field_value for the template.
 * When an "activity" is in the payload it will be rendered automatically and is then available as "rendered_activity".
 */
class GenericSubjectRenderer extends Renderer
{
    protected function validate()
    {
        $activity = $this->getPayload('activity');
        if ($activity && !$activity instanceof ActivityInterface) {
            throw new RuntimeError('Optional payload "activity" must implement: ' . ActivityInterface::CLASS);
        }
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/subject/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['html_attributes'] = $this->getOption('html_attributes', []);

        $params['field_name'] = $this->getOption('field_name', 'Missing "field_name" setting.');

        if ($this->hasOption('expression')) {
            $payload = [];
            foreach ($this->payload as $key => $val) {
                $payload[$key] = $val;
            }
            $params['field_value'] = $this->expression_service->evaluate($this->getOption('expression'), $payload);
        } else {
            $params['field_value'] = $this->getOption('field_value', 'Missing "field_value" or "expression" setting.');
        }

        if ($this->hasPayload('activity')) {
            $activity = $this->getPayload('activity')->toArray();
            $params['activity'] = $activity;
            $params['rendered_activity'] = $this->renderer_service->renderSubject(
                $activity,
                $this->output_format,
                $this->config,
                [],
                $this->settings
            );
        }

        if ($this->hasPayload('resource')) {
            $params['resource'] = $this->getPayload('resource')->toArray();
        }

        $params['css'] = (string)$this->getOption('css', '');

        return $params;
    }
}
