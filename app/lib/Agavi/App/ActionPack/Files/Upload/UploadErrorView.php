<?php

namespace Honeygavi\Agavi\App\ActionPack\Files\Upload;

use Honeygavi\Agavi\App\Base\View;
use AgaviRequestDataHolder;
use Trellis\Runtime\Attribute\HandlesFileListInterface;

class UploadErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $this->prepareTemplateAttributes($request_data);

        // reuse input view template tp display form and errors
        $this->getLayer('content')->setTemplate('Files/Upload/UploadInput');
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        // when specific error messages are not translated they get the default error message
        // and thus multiple validation incidents may lead to repeating "translations"
        $unique_errors = array_reverse(array_keys(array_flip($this->getErrorMessages())));

        $payload = [
            'success' => false,
            'messages' => $unique_errors
        ];

        $this->getResponse()->setHttpStatusCode(400);

        return json_encode($payload);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->cliError(
            'Errors:' . PHP_EOL . implode(PHP_EOL, $this->getAttribute('errors', []))
        );
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
