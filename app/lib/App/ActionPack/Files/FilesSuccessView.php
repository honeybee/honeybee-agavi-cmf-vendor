<?php

namespace Honeygavi\App\ActionPack\Files;

use Honeygavi\App\Base\View;
use AgaviRequestDataHolder;

class FilesSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $this->prepareTemplateAttributes($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->prepareTemplateAttributes($request_data);

        $payload = [
            'self' => $this->routing->gen(null),
            'upload_url' => $this->getAttribute('upload_url'),
            'filesystem' => $this->getAttribute('filesystem'),
            'files' => $this->getAttribute('files')
        ];

        return json_encode($payload, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $this->prepareTemplateAttributes($request_data);

        $payload = [
            'filesystem' => $this->getAttribute('filesystem'),
            'files' => $this->getAttribute('files')
        ];

        return json_encode($payload, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    }

    protected function prepareTemplateAttributes(AgaviRequestDataHolder $request_data)
    {
        $fss = $this->getServiceLocator()->getFilesystemService();
        $art = $this->getAttribute('aggregate_root_type');

        $prefix = $fss->getPrefix($art);
        $scheme = $fss->getScheme($art);

        $files_meta = $fss->listContents($prefix, true);

        $files = [];
        foreach ($files_meta as $info) {
            if ($info['type'] !== 'file') {
                continue;
            }
            $files[$info['path']] = array_merge(
                $info,
                [],
                [
                    'download_url' => $this->routing->gen(
                        'module.files.download',
                        [ 'module' => $art, 'file' => $info['path'] ]
                    )
                ]
            );
        }

        $this->setAttribute('filesystem', $scheme);
        $this->setAttribute('files', $files);

        $file_attributes = [];
        foreach ($art->getFileHandlingAttributes() as $attribute_path => $attribute) {
            $file_attributes[$attribute_path] = [
                'name' => $attribute->getName(),
                'path' => $attribute->getPath(),
                'multiple' => ($attribute instanceof HandlesFileListInterface) ? 'multiple' : ''
            ];
        }

        $this->setAttribute('file_attributes', $file_attributes);
        $this->setAttribute('temp_filesystem', $fss->getTempScheme($art));
        $this->setAttribute('upload_url', $this->routing->gen('module.files.upload', [ 'module' => $art ]));
    }
}
