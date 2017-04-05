<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_System_Status_StatusErrorView extends View
{
    const JSON_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE;

    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('_title', 'Status retrieval failed');
        $this->setupHtml($request_data);
        $this->getResponse()->setHttpStatusCode('500');
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('500');
        return json_encode(['error' => 'Unexpected internal error on getting status information'], self::JSON_OPTIONS);
    }

    public function executeXml(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('500');
        return '<?xml version="1.0" encoding="UTF-8"?><error>Error while retrieving status information</error>';
    }

    public function executeText(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('500');
        return 'Error while retrieving status information';
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        return $this->cliError('Error while retrieving status information', 127);
    }
}
