<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Files\Upload;

use AgaviRequestDataHolder;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class UploadSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $payload = $this->getPayload($request_data);

        foreach ($payload as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $payload = $this->getPayload($request_data);

        return json_encode($payload, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    }

    public function getPayload(AgaviRequestDataHolder $request_data)
    {
        $fss = $this->getServiceLocator()->getFilesystemService();
        $art = $this->getAttribute('aggregate_root_type');
        $attribute = $request_data->getParameter('attribute');
        $files = $request_data->getFile('uploadform');

        $attribute_path = null;
        $uploaded_file = null;
        foreach ($files as $name => $file) {
            if ($attribute->getPath() === $name) {
                $attribute_path = $name;
                $uploaded_file = $file;
            }
        }

        if ($uploaded_file === null) {
            throw new RuntimeError('Validation did not export the uploaded file with the appropriate attribute.');
        }

        $payload = [
            'success' => true,
            'filesystem' => $fss->getTempScheme($art),
            'attribute_name' => $attribute->getName(),
            'attribute_path' => $attribute->getPath()
        ];

        $payload['file'] = [];
        $payload['file']['location'] = $file->getLocation();
        $payload['file']['filesize'] = $file->getFilesize();
        $payload['file']['mimetype'] = $file->getMimetype();
        $payload['file']['download_url'] = $this->routing->gen(
            'module.files.download',
            [
                'file' => $file->getLocation(),
                'module' => $art,
                // 'attribute' => $attribute
            ]
        );

        return $payload;
    }
}
