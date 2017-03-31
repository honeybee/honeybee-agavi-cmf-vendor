<?php

namespace Honeygavi\Agavi\App\ActionPack\Resource\Embed;

use AgaviRequestDataHolder;
use Honeybee\EntityInterface;
use Honeygavi\Agavi\App\Base\View;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Trellis\Runtime\Attribute\ListAttribute;

class EmbedSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        /** @var Honeybee\Projection\ProjectionInterface **/
        $resource = $this->getAttribute('resource');
        /** @var Honeybee\Entity **/
        $embed = $this->getAttribute('embed');
        /** @var Trellis\Runtime\Attribute\AttributeInterface **/
        $parent_attribute = $embed->getType()->getParentAttribute();

        /*
            Embed can be given rendering options through the following fallback sequence of configs:

            - parent attribute type (entity_reference_list_attribute / embedded_entity_list_attribute)
            - parent attribute name (field_<parent_name>)
            - embed name (vendor.package.type.parent_attribute.embed_type)
            - '__all_fields' on the parent entity
            - '__fields_options' on the parent entity
        */

        // get eventual rendering settings propagated by the embed's parent attribute/entity
        $parent_attribute_settings = $this->getParentAttributeSettings($parent_attribute, $resource);
        $parent_entity_settings = $this->getParentEntitySettings($embed, $parent_attribute);
        $ancestors_settings = new ArrayConfig(array_replace_recursive(
            $parent_attribute_settings->toArray(),
            $parent_entity_settings->toArray()
        ));

        // default inherited settings
        $default_settings = [
            'view_scope' => $ancestors_settings->get('view_scope', $this->getViewScope())
        ];
        if ($request_data->hasParameter('input_group') || $ancestors_settings->has('group_parts')) {
            $default_settings['group_parts'] = $request_data->getParameter(
                'input_group',
                $ancestors_settings->get('group_parts')
            );
        }
        if ($ancestors_settings->has('entity_template')) {
            $default_settings['template'] = $ancestors_settings->get('entity_template');
        }
        // subject settings
        $embed_renderer_config = $this->getServiceLocator()->getViewConfigService()->getRendererConfig(
            $this->getViewScope(),
            $this->getOutputFormat(),
            $embed
        );

        $default_settings = array_replace_recursive($default_settings, $embed_renderer_config->toArray());
        $renderer_settings = $this->getResourceRendererSettings($default_settings);

        $rendered_embed = $this->renderSubject($embed, $renderer_settings);
        $this->setAttribute('rendered_embed', $rendered_embed);
    }

    protected function getResourceRendererSettings($default_settings = [])
    {
        // optional in-code specific settings
        return array_replace_recursive([], $default_settings);
    }

    protected function getParentAttributeSettings(ListAttribute $parent_attribute = null, EntityInterface $resource = null)
    {
        $parent_attribute_settings = [];
        $view_scope = $this->getViewScope();
        $view_config_service = $this->getServiceLocator()->getViewConfigService();

        if ($parent_attribute) {
            // view_config
            $parent_type_renderer_config = $view_config_service->getRendererConfig(
                $view_scope,
                $this->getOutputFormat(),
                $parent_attribute
            );
            $parent_renderer_config = $view_config_service->getRendererConfig(
                $view_scope,
                $this->getOutputFormat(),
                'field_' . $parent_attribute->getName()
            );
            // view_template
            $parent_render_settings = [];
            if ($resource) {
                $view_template = $this->getServiceLocator()->getViewTemplateService()->getViewTemplate(
                    $view_scope,
                    $resource->getType()->getPrefix(),
                    $this->getOutputFormat()
                );
                $view_template_fields = $view_template->extractAllFields();
                $parent_render_settings = $view_template_fields[$parent_attribute->getPath()]->getConfig();
            }

            $parent_attribute_settings = array_replace_recursive(
                $parent_type_renderer_config->toArray(),
                $parent_renderer_config->toArray(),
                $parent_render_settings->toArray()
            );
        }

        return new ArrayConfig($parent_attribute_settings);
    }

    protected function getParentEntitySettings(EntityInterface $embed, ListAttribute $parent_attribute)
    {
        $parent_entity_settings = [];
        $view_scope = $this->getViewScope();
        $view_config_service = $this->getServiceLocator()->getViewConfigService();

        $parent_entity = $embed->getParent();

        if ($parent_entity) {
            // settings specified on parent entity
            $entity_renderer_config = $view_config_service->getRendererConfig(
                $view_scope,
                $this->getOutputFormat(),
                $parent_entity
            );
            $all_fields_options = $entity_renderer_config->get('__all_fields', new ArrayConfig([]));
            $specific_fields_options = $entity_renderer_config->get('__fields_options', new ArrayConfig([]));
            $parent_field_options = $specific_fields_options->get(
                'field_' . $parent_attribute->getName(),
                new ArrayConfig([])
            );
            $parent_entity_settings = array_replace_recursive(
                $all_fields_options->toArray(),
                $parent_field_options->toArray()
            );
        }

        return new ArrayConfig($parent_entity_settings);
    }
}
