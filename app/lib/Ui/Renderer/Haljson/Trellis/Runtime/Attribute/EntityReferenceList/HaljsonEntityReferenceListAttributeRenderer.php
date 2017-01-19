<?php

namespace Honeybee\Ui\Renderer\Haljson\Trellis\Runtime\Attribute\EntityReferenceList;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Projection\ReferencedEntity;
use Honeybee\Ui\Renderer\AttributeRenderer;
use Trellis\Runtime\Attribute\AttributeValuePath;

/**
 * EntityReferenceListAttribute renderer for haljson output format.
 */
class HaljsonEntityReferenceListAttributeRenderer extends AttributeRenderer
{
    protected function doRender()
    {
        if ($this->hasOption('value')) {
            return $this->getOption('value');
        }

        $resource = $this->getPayload('resource');
        $root_resource = $resource->getRoot() ?: $resource;

        $value_path = $this->getOption('attribute_value_path');
        if (!empty($value_path)) {
            $value = AttributeValuePath::getAttributeValueByPath($resource, $value_path);
        } else {
            $value = $resource->getValue($this->attribute->getName());
        }

        $rendered_entities = [];
        foreach ($value as $entity) {
            $rendered_entity = $this->renderer_service->renderSubject(
                $entity,
                $this->output_format,
                new ArrayConfig($this->getOptions()) // todo don't pass everything on?
            );

            if (!is_array($rendered_entity)) {
                throw new RuntimeError('Rendered entity should be an array for haljson rendering.');
            }

            $rendered_entity['_links'] = $this->buildLinks($entity);

            foreach ((array)$this->getOption('exclude_properties', []) as $property) {
                unset($rendered_entity[$property]);
            }

            $rendered_entities[] = $rendered_entity;
        }

        return $rendered_entities;
    }

    protected function buildLinks(ReferencedEntity $resource)
    {
        $links = [];
        $resource_type_class = $this->attribute->getEmbeddedTypeByReferencedPrefix(
                $resource->getType()->getPrefix()
            )->getReferencedTypeClass();
        $art = $this->resource_type_map->getByClassName($resource_type_class);
        $user = $this->environment->getUser();

        // view_resource
        if ($this->getOption('add_view_resource_link', true) && $user->isAllowed($art->getPrefix(), 'view_resource')) {
            $view_resource_url = $this->url_generator->generateUrl(
                'module.resource',
                [
                    'module' => $art,
                    'resource' => $resource->getIdentifier()
                ]
            );
            $link = [
                'href' => $view_resource_url,
                'name' => 'view_resource',
            ];
            $links['honeybee:' . $art->getPrefix() . '~view_resource'] = $link;
        }

        return $links;
    }
}
