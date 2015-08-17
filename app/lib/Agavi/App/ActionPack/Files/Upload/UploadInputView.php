<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Files\Upload;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;

class UploadInputView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $this->prepareTemplateAttributes($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->prepareTemplateAttributes($request_data);

        $payload = array_merge([], $this->getAttribute('file_attributes'));

        return json_encode($payload, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    }

    protected function prepareTemplateAttributes(AgaviRequestDataHolder $request_data)
    {
        $art = $this->getAttribute('aggregate_root_type');

        $fss = $this->getServiceLocator()->getFilesystemService();

        $prefix = $fss->getPrefix($art);
        $scheme = $fss->getScheme($art);

        $this->setAttribute('filesystem', $scheme);

        // TODO if an attribute was provided only provide a submit form for that attribute or set as selected option
        $file_attributes = [];
        foreach ($art->getFileHandlingAttributes() as $attribute_path => $attribute) {
            $file_attributes[$attribute_path] = [
                'name' => $attribute->getName(),
                'path' => $attribute->getPath(),
                'multiple' => ($attribute instanceof HandlesFileListInterface) ? 'multiple' : '',
                // 'upload_url' => $this->routing->gen('module.files.upload', [ 'attribute' => $attribute ])
            ];
        }

        $this->setAttribute('upload_url', $this->routing->gen('module.files.upload', [ 'module' => $art ]));
        $this->setAttribute('file_attributes', $file_attributes);
        $this->setAttribute('temp_filesystem', $fss->getTempScheme($art));
    }
}
