<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Files\Download;

use Workflux\ExecutionContextInterface;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use AgaviRequestDataHolder;
use Honeybee\Common\Util\FileToolkit;

class DownloadSuccessView extends View
{
    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        $response = $this->getResponse();

        $fss = $this->getServiceLocator()->getFilesystemService();

        $art = $this->getAttribute('aggregate_root_type');

        $file_id = $request_data->getParameter('file');

        $uri = $request_data->getParameter('file_uri');
        if (empty($uri)) {
            $this->logError('Necessary request parameter from validation not provided: file_uri');
            $response->setHttpStatusCode(500);
            return;
        }

        $last_modified_time = $fss->getTimestamp($uri);
        $etag = $file_id . '-' . $last_modified_time;
        $response->setHttpHeader('Last-Modified', gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
        $response->setHttpHeader('Etag', $etag); // is timestamp sufficient as ETag?

        $etag_from_request = trim($request_data->getHeader('If-None-Match', ''));
        $timestamp_from_request = $request_data->getHeader('If-Modified-Since', false);

        $equal_time = $timestamp_from_request !== false && @strtotime($timestamp_from_request) == $last_modified_time;

        if (($etag_from_request === $etag) || $equal_time) {
            $response->setHttpStatusCode(304);
            return;
        }

        $disposition = 'inline';
        if ($request_data->getParameter('download') !== null) {
            $disposition = 'attachment';
        }

        $content_type = $fss->getMimetype($uri);

        // TODO do we need this? with X-Sendfile header and HTTP 1.1 it's not mandatory I think, attribute info?
        $content_length = $fss->getSize($uri);

        $file_name = FileToolkit::slugify($file_id);

        $content_disposition = $disposition . '; filename=' . $file_name;

        $response->setHttpHeader('Content-Type', $content_type);
        $response->setHttpHeader('Content-Length', $content_length);
        $response->setHttpHeader('Content-Disposition', $content_disposition);

        $resource = $fss->readStream($uri);

        // $this->logInfo('Download request for', $uri, $resource);

        $response->setContent($resource);
    }
}
