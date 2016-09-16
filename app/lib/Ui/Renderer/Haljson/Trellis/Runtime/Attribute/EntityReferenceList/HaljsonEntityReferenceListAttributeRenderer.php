<?php

namespace Honeybee\Ui\Renderer\Haljson\Trellis\Runtime\Attribute\EntityReferenceList;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ArrayConfig;
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

            if (isset($rendered_entity['@type'])) {
                $referenced_type_class = $this->attribute->getEmbeddedTypeByReferencedPrefix(
                    $rendered_entity['@type']
                )->getReferencedTypeClass();

                $art = $this->resource_type_map->getByClassName($referenced_type_class);

                $view_resource_url = $this->url_generator->generateUrl(
                    'module.resource',
                    [
                        'module' => $art,
                        'resource' => $rendered_entity['referenced_identifier']
                    ]
                );

                $rendered_entity['_links'] = [];
                $link = [
                    'href' => $view_resource_url,
                    'name' => 'view_resource',
                ];
                $rendered_entity['_links']['honeybee:'.$art->getPrefix().'~view_resource'] = $link;
            } else {
                $this->logger->error('No @type in reference data: ' . var_export($rendered_entity, true));
            }

            foreach ((array)$this->getOption('exclude_properties', []) as $property) {
                unset($rendered_entity[$property]);
            }

            $rendered_entities[] = $rendered_entity;
        }

        return $rendered_entities;
    }
}
