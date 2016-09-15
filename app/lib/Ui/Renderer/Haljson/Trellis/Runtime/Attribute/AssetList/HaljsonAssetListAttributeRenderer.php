<?php

namespace Honeybee\Ui\Renderer\Haljson\Trellis\Runtime\Attribute\AssetList;

use Honeybee\Ui\Renderer\AttributeRenderer;
use Trellis\Runtime\Attribute\AttributeValuePath;

/**
 * ImageListAttribute renderer for haljson output format.
 */
class HaljsonAssetListAttributeRenderer extends AttributeRenderer
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
            $assets = AttributeValuePath::getAttributeValueByPath($resource, $value_path);
        } else {
            $assets = $resource->getValue($this->attribute->getName());
        }

        $json_data = [];

        foreach ($assets as $asset) {
            $asset_data = $asset->toArray();

            $download_url = $this->url_generator->generateUrl(
                'module.files.download',
                [
                    'resource' => $root_resource,
                    'file' => $asset->getLocation(),
                ]
            );
            $asset_data['download_url'] = $download_url;

            foreach ((array)$this->getOption('exclude_properties', []) as $property) {
                unset($asset_data[$property]);
            }

            $json_data[] = $asset_data;
        }

        return $json_data;
    }
}
