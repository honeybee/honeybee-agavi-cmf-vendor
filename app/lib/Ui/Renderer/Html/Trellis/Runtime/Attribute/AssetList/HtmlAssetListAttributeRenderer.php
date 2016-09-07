<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\AssetList;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;

class HtmlAssetListAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        if (StringToolkit::endsWith($view_scope, 'collection')) {
            return $this->output_format->getName() . '/attribute/asset-list/as_itemlist_item_cell.twig';
        }

        return $this->output_format->getName() . '/attribute/asset-list/as_input.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $resource = $this->getPayload('resource');

        $assets = [];
        $root_doc = $resource->getRoot() ?: $resource;
        foreach ($params['attribute_value'] as $asset) {
            $original_asset_url = $this->url_generator->generateUrl(
                'module.files.download',
                [ 'resource' => $root_doc, 'file' => $asset->getLocation() ]
            );

            $additional_file_info = [
                'id' => md5($asset->getLocation()),
                'download_url' => $original_asset_url
            ];

            $assets[] = array_merge($asset->toNative(), $additional_file_info);
        }

        $upload_input_name = $this->getOption('form-name', 'uploadform') . '[' . $this->attribute->getPath() . ']';

        $params['assets'] = $assets;
        $params['resource_type_prefix'] = $this->attribute->getRootType()->getPrefix();
        $params['resource_type_name'] = $root_doc->getType()->getName();
        $params['resource_identifier'] = $root_doc->getIdentifier();
        $params['upload_input_name'] = $upload_input_name;
        $params['upload_url'] = $this->url_generator->generateUrl('module.files.upload', [ 'resource' => $root_doc ]);

        return $params;
    }

    protected function determineAttributeValue($attribute_name)
    {
        $value = [];

        if ($this->hasOption('value')) {
            return (array)$this->getOption('value');
        }

        $expression = $this->getOption('expression');
        if (!empty($expression)) {
            $value = $this->evaluateExpression($expression);
        } else {
            $value = $this->getPayload('resource')->getValue($attribute_name);
        }

        $value = is_array($value) ? $value : [ $value ];

        if ($value === $this->attribute->getNullValue()) {
            return [];
        } else {
            return $value;
        }
    }

    protected function getInputTemplateParameters()
    {
        $global_input_parameters = parent::getInputTemplateParameters();

        if (!empty($global_input_parameters['readonly'])) {
            $global_input_parameters['disabled'] = 'disabled';
        }

        return $global_input_parameters;
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/AssetList');
    }
}
