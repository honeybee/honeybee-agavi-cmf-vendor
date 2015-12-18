<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_System_Status_StatusSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $message = 'status?';
        $this->setAttribute('_title', $message);
        return "<p>$message</p>";
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return json_encode(['message' => 'status?']);
    }

    public function executeXml(AgaviRequestDataHolder $request_data)
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><message>Status?</message>";
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        return $this->cliMessage('status?');
    }
/*
    public function executeAtomxml(AgaviRequestDataHolder $request_data)
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><feed xmlns=\"http://www.w3.org/2005/Atom\"><title>status?</title></feed>";
    }

    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('406');
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent('status?');
    }

    public function executePdf(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('406');
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent('status?');
    }
*/
}
