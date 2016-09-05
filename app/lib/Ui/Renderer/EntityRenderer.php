<?php

namespace Honeybee\Ui\Renderer;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\ArrayToolkit;
use Honeybee\EntityInterface;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Projection\ProjectionInterface;
use Honeybee\Ui\Renderer\AttributeRenderer;

abstract class EntityRenderer extends Renderer
{
    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof EntityInterface) {
            throw new RuntimeError(sprintf('Payload "subject" must implement "%s".', EntityInterface::CLASS));
        }
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/resource/as_itemlist_item.twig';
    }

    protected function getTemplateParameters()
    {
        $entity = $this->getPayload('subject');
        $group_parts = (array)$this->getOption('group_parts', []);
        $parent_attribute = $entity->getType()->getParentAttribute();

        $params = parent::getTemplateParameters();

        $params['has_parent_attribute'] = $parent_attribute !== null;
        $params['grouped_base_path'] = ArrayToolkit::flattenToArrayPath($group_parts);
        $params['resource'] = $entity->toArray();
        $params['entity_type'] = $entity->getType()->getPrefix();
        $params['is_embed_template'] = $this->getOption('is_embed_template', false);
        $params['is_new'] = !$entity->hasValue('identifier');
        // when entity is part of a list (embed or reference)
        $params['add_item_to_parent_list_allowed'] = $this->getOption('add_item_to_parent_list_allowed', true);

        // custom title+decription per-embedded-item in an EntityList attribute
        if (!$entity->getType()->isRoot()) {
            $params['embed_item_title'] = $this->getOption('entity_title', 'entity_title');
            $params['embed_item_description'] = $this->getOption('entity_description', 'entity_description');
        }

        $params = array_replace_recursive($this->lookupViewTemplate(), $params);

        $params['rendered_fields'] = $this->getRenderedFields($entity, $params['view_template']);
        if ($entity instanceof ProjectionInterface && $entity->getWorkflowState()) {
            $params['rendered_resource_activities'] = $this->getResourceActivities($entity);
        }

        return $params;
    }

    protected function getRenderedFields(EntityInterface $entity, $view_template)
    {
        $rendered_fields = [];

        $entity_type = $entity->getType();

        $fields = $view_template->extractAllFields();
        foreach ($fields as $field_name => $field) {
            $field_settings = $field->getConfig();
            $attribute = null;
            if ($field_settings->has('attribute_path')) {
                $attribute = $entity_type->getAttribute($field_settings->get('attribute_path'));
            }

            $default_render_settings = [
                'group_parts' => $this->getOption('group_parts', [ $entity_type->getPrefix() ]),
                'field_name' => $field->getName(),
                'view_scope' => $this->getOption('view_scope'),
                'is_within_embed_template' => $this->getOption('is_embed_template', false),
                'readonly' => $this->getOption('readonly', false),
            ];

            $attribute_type_renderer_config = $this->view_config_service->getRendererConfig(
                $this->getOption('view_scope', 'missing.view_scope'),
                $this->output_format,
                $attribute
            )->toArray();
            $field_renderer_config = $this->view_config_service->getRendererConfig(
                $this->getOption('view_scope', 'missing.view_scope'),
                $this->output_format,
                'field_' . $field->getName()
            )->toArray();

            if ($field_settings->has('renderer')) {
                $field_renderer_config['renderer'] = $field_settings->get('renderer');
            }

            $renderer_config = array_replace_recursive($attribute_type_renderer_config, $field_renderer_config);

            $render_settings = array_replace_recursive(
                $default_render_settings,
                $renderer_config,
                $field_settings->toArray(),
                $this->getOption('__fields_options', new Settings())->get($field_name, new Settings())->toArray()
            );

            if ($attribute) {
                $renderer = $this->renderer_service->getRenderer($attribute, $this->output_format, new ArrayConfig($renderer_config));
                $rendered_field = $renderer->render(
                    [
                        'attribute' => $attribute,
                        'resource' => $entity
                    ],
                    $render_settings
                );
            } else {
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
                $renderer = $this->renderer_service->getRenderer(null, $this->output_format, $renderer_config);
                $rendered_field = $renderer->render([ 'resource' => $entity ], $render_settings);
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

    protected function getResourceActivities(ProjectionInterface $resource)
    {
        $activity_map = $this->activity_service->getActivityMap($resource);

        $view_scope = $this->getOption('view_scope', $resource->getScopeKey());

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

        $rendered_activity_map = $activity_map_renderer->render(
            [ 'subject' => $activity_map, 'resource' => $resource ],
            $this->settings
        );

        return $rendered_activity_map;
    }

    protected function lookupViewTemplate()
    {
        $view_template_name = $this->getOption('view_template_name');
        if (!$this->hasOption('view_template_name')) {
            // should it be different for glances?
            $view_template_name = $this->name_resolver->resolve($this->getPayload('subject'));
        }

        $view_template = $this->view_template_service->getViewTemplate(
            $this->getOption('view_scope', 'default.resource'),
            $view_template_name,
            $this->output_format
        );

        return [
            'view_template' => $view_template,
            'view_template_name' => $view_template_name
        ];
    }

    /**
     * Entities can have different translation-keys depending on the current state.
     * To provide translation just for one specific state define a translation key with the state
     * appended after the translation key name:
     *
     *      e.g. translation key 'message' can have a specific translation when the resource is 'inactive',
     *      and that can be defined with a translation key 'message.inactive' in the translations.xml
     *
     * Check the Workflow.xml of the interested Resource for a list of available states.
     * If no 'per-state' translation is defined then the general translation key will be used as fallback:
     *
     *      e.g. 'message' if 'message.inactive' has not been defined
     *
     * If neither the fallback exists then the translation will not be included.
     *
     * @return array Translated strings to use in the template
     */
    protected function getTranslations($translation_domain = null)
    {
        $translation_keys = $this->getTranslationKeys($translation_domain);
        $translations = [];

        $resource_current_state = $this->getPayload('subject') instanceof ProjectionInterface
            ? $this->getPayload('subject')->getWorkflowState()
            : $this->getPayload('subject')->getRoot()->getWorkflowState();

        foreach ($translation_keys as $index => $key) {
            $translation_key = sprintf('%s.%s', $key, $resource_current_state);
            $translation = $this->_($translation_key, $translation_domain, null, null, '');

            // if a translation doesn't exist for the current state fallback to the stateless translation
            if (empty($translation)) {
                $translation_key = $key;
                $translation = $this->_($translation_key, $translation_domain, null, null, '');
            }
            // add just keys  having a corresponding translations
            if (!empty($translation)) {
                $translations[$index] = $translation;
            }
        }
        return $translations;
    }

    protected function getDefaultTranslationDomain()
    {
        $entity = $this->getPayload('subject');

        $parent_attribute = $entity->getType()->getParentAttribute();
        $translation_domain_pieces = [];

        if ($parent_attribute) {
            $translation_domain_pieces[] = $entity->getRoot()->getType()->getScopeKey();
            // $translation_domain_pieces[] = AttributeRenderer::STATIC_TRANSLATION_PATH;
            // $translation_domain_pieces[] = $parent_attribute->getPath();
            // $translation_domain_pieces[] = $entity->getType()->getPrefix();
        } else {
            $translation_domain_pieces[] = $entity->getType()->getScopeKey();
        }

        return implode('.', $translation_domain_pieces);
    }
}
