<?php

namespace Honeygavi\Ui\Renderer\Haljson\Honeybee;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\EntityInterface;
use Honeybee\Projection\ProjectionInterface;
use Honeygavi\Ui\Renderer\EntityRenderer;

class HaljsonEntityRenderer extends EntityRenderer
{
    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof EntityInterface) {
            throw new RuntimeError('Payload "subject" must implement: ' . EntityInterface::CLASS);
        }
    }

    /**
     * @return array
     */
    protected function doRender()
    {
        $entity = $this->getPayload('subject');

        $view_scope = $this->getOption('view_scope', 'missing.entity.view_scope');

        $view_template_name = $this->getOption('view_template_name');
        if (!$this->hasOption('view_template_name')) {
            $view_template_name = $this->name_resolver->resolve($entity);
        }

        $view_template = null;
        try {
            $view_template = $this->view_template_service->getViewTemplate(
                $view_scope,
                $view_template_name,
                $this->output_format
            );
        } catch (RuntimeError $e) {
        }


        $json = [];
        // TODO introduce option?
        if ($view_template !== null) {
            $json = $this->getRenderedFields($entity, $view_template);
        } else {
            $json = $entity->toArray();
        }
        // $json['title'] = $entity->getUsername();

        if ($entity instanceof ProjectionInterface && $entity->getWorkflowState()) {
            $json['_links'] = $this->getResourceActivities($entity);
        }

        return $json;
    }
}
