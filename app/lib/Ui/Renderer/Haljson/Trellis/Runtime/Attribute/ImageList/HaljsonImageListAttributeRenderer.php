<?php

namespace Honeybee\Ui\Renderer\Haljson\Trellis\Runtime\Attribute\ImageList;

use Honeybee\Ui\Renderer\AttributeRenderer;
use Trellis\Runtime\Attribute\AttributeValuePath;

/**
 * ImageListAttribute renderer for haljson output format.
 */
class HaljsonImageListAttributeRenderer extends AttributeRenderer
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
            $images = AttributeValuePath::getAttributeValueByPath($resource, $value_path);
        } else {
            $images = $resource->getValue($this->attribute->getName());
        }

        $json_data = [];

        foreach ($images as $image) {
            $image_data = $image->toArray();

            $download_url = $this->url_generator->generateUrl(
                'module.files.download',
                [
                    'resource' => $root_resource,
                    'file' => $image->getLocation(),
                ]
            );
            $image_data['download_url'] = $download_url;

            foreach ((array)$this->getOption('exclude_properties', []) as $property) {
                unset($image_data[$property]);
            }

            $json_data[] = $image_data;
        }

        return $json_data;
    }
}
