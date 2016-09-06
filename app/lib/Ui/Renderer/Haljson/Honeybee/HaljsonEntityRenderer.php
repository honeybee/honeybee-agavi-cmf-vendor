<?php

namespace Honeybee\Ui\Renderer\Haljson\Honeybee;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\EntityInterface;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Projection\ProjectionInterface;
use Honeybee\Ui\Renderer\Haljson\HaljsonRenderer;

class HaljsonEntityRenderer extends HaljsonRenderer
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
        $params = parent::getTemplateParameters();

        $entity = $this->getPayload('subject');

        $view_scope = $this->getOption('view_scope', 'missing.view_scope');

        $view_template_name = $this->getOption('view_template_name');
        if (!$this->hasOption('view_template_name')) {
            $view_template_name = $this->name_resolver->resolve($entity);
        }

        $view_template = $this->view_template_service->getViewTemplate(
            $view_scope,
            $view_template_name,
            $this->output_format
        );

        $json = [];
        // TODO introduce option?
        //$json = $entity->toArray();
        $json = $this->getRenderedFields($entity, $view_template);
        // $json['title'] = $entity->getUsername();
        if ($entity instanceof ProjectionInterface && $entity->getWorkflowState()) {
            $json['_links'] = $this->getResourceActivities($entity);
        }
        return $json;
    }

    protected function getRenderedFields(EntityInterface $entity, $view_template)
    {
        $rendered_fields = [];

        $entity_type = $entity->getType();

        $fields_options = $this->getOption('__fields_options', new Settings());
        $fields = $view_template->extractAllFields();
        foreach ($fields as $field_name => $field) {
            $attribute = null;
            $field_config = $field->getConfig();
            if ($field_config->has('attribute_path')) {
                $attribute = $entity_type->getAttribute($field_config->get('attribute_path'));
            }

            if ($attribute) {
                $rendered_field = $this->determineAttributeValue($entity, $attribute->getName());
            } else {
                $renderer_config = new ArrayConfig($field_config->toArray());
                if (!$renderer_config->has('renderer')) {
                    throw new RuntimeError(
                        sprintf(
                            'When no "attribute_path" is given a "renderer" setting is mandatory on ' .
                            'field "%s" in view template "%s" of view scope "%s" for type "%s".',
                            $field->getName(),
                            $view_template->getName(),
                            $this->getOption('view_scope', ''),
                            $entity->getType()->getPrefix()
                        )
                    );
                }
                // gets the renderer that has been specified in the config
                $renderer = $this->renderer_service->getRenderer(null, $this->output_format, $renderer_config);
                $rendered_field = $renderer->render([ 'resource' => $entity ]);
            }

            // todo index should be tab->panel->row->item->group->field instead of field only
            // as the same field could be rendered in different positions with different css
            // on the other hand fields can have unique names and still use the same attribute(-path)
            if (isset($rendered_fields[$field->getName()])) {
                throw new RuntimeError(
                    sprintf(
                        'Field "%s" defined multiple times. Please rename the field ' .
                        'in view template "%s" of view scope "%s" for type "%s".',
                        $field->getName(),
                        $view_template->getName(),
                        $this->getOption('view_scope', ''),
                        $entity->getType()->getPrefix()
                    )
                );
            }

            $rendered_fields[$field->getName()] = $rendered_field;
        }

        return $rendered_fields;
    }

    protected function determineAttributeValue(EntityInterface $entity, $attribute_name)
    {
        $value = '';

        if ($this->hasOption('value')) {
            return $this->getOption('value');
        }

        $expression = $this->getOption('expression');
        $value_path = $this->getOption('attribute_value_path');
        if (!empty($value_path)) {
            $value = AttributeValuePath::getAttributeValueByPath($entity, $value_path);
        } else {
            $value = $entity->getValue($attribute_name);
        }

        if (is_object($value)) {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format(TimestampAttribute::FORMAT_ISO8601);
            } elseif ($value instanceof ComplexValueInterface) {
                $value = $value->toArray();
            } else {
                // it's a complex value so we should not convert the object to string or similar
                // the specific renderer for that attribute might want to use the actual object
            }
        }

        return $value;
    }

    protected function getResourceActivities(ProjectionInterface $resource)
    {
        $activity_map = $this->activity_service->getActivityMap($resource);

        $view_scope = $this->getOption('view_scope', $resource->getScopeKey());

        $activities = [];
        foreach ($activity_map as $activity) {
            $activities[$activity->getName()] = $activity->getUrl();
        }
        $default_data = [
            'view_scope' => $view_scope,
            'translation_domain' => $resource->getType()->getPrefix() . '.activity'
        ];

        $default_renderer_config_name = $resource->getScopeKey() . '.activity_map';
        $renderer_config = $this->view_config_service->getRendererConfig(
            $view_scope,
            $this->output_format,
            $this->getOption('resource_activity_map_settings_key', $default_renderer_config_name),
            $default_data
        );

        $activity_map_renderer = $this->renderer_service->getRenderer(
            $activity_map,
            $this->output_format,
            $renderer_config
        );

        $activities = $activity_map_renderer->render(
            [ 'subject' => $activity_map, 'resource' => $resource ],
            $this->settings
        );

        return $activities;
    }
}
