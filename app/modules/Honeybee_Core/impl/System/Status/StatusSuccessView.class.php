<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

class Honeybee_Core_System_Status_StatusSuccessView extends View
{
    const JSON_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE;

    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        // $this->setAttribute('_title', 'Status');

        $message = $this->getInfosAsString();

        return '<html><head><title>Status</title></head><body><pre>' . $message . '</pre></body></html>';
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return json_encode($this->getBody($request_data), self::JSON_OPTIONS);
    }

    public function executeXml(AgaviRequestDataHolder $request_data)
    {
        $body = $this->getBody($request_data);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('application');

        $attr = $dom->createAttribute('name');
        $attr->value = $body['application'];
        $root->appendChild($attr);

        $attr = $dom->createAttribute('status');
        $attr->value = $body['status'];
        $root->appendChild($attr);

        $summary = $dom->createElement('summary');
        $summary->appendChild(new DOMText($body['message']));
        foreach ($body['summary'] as $name => $value) {
            $attr = $dom->createAttribute($name);
            $attr->value = $value;
            $summary->appendChild($attr);
        }
        $root->appendChild($summary);

        $dom->appendChild($root);

        return $dom->saveXML();
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = $this->getInfosAsString();

        if ($this->getAttribute('failing', 0) > 0) {
            return $this->cliError($message);
        }

        return $this->cliMessage($message);
    }

    protected function getInfosAsString()
    {
        $verbose = $this->getAttribute('verbose', false);
        $infos = $this->getAttribute('infos', []);

        $message = sprintf("Status for application: %s\n\n", AgaviConfig::get('core.app_name'));

        foreach ($infos as $status) {
            if ($verbose) {
                $message .= sprintf(
                    "- %s = %s (%s) %s\n",
                    $status->getConnectionName(),
                    $status->getStatus(),
                    $status->getImplementor(),
                    json_encode($status->getInfo(), self::JSON_OPTIONS)
                );
            } else {
                $message .= sprintf(
                    "- %s = %s\n",
                    $status->getConnectionName(),
                    $status->getStatus()
                );
            }
        }

        $message .= sprintf(
            "\nConnection status summary: failing=%d working=%d unknown=%d",
            $this->getAttribute('failing'),
            $this->getAttribute('working'),
            $this->getAttribute('unknown')
        );

        return $message;
    }

    protected function getBody(AgaviRequestDataHolder $request_data)
    {
        $verbose = $this->getAttribute('verbose', false);
        $infos = $this->getAttribute('infos', []);

        $details = [];
        foreach ($infos as $status) {
            if ($verbose) {
                $details[$status->getConnectionName()] = $status;
            } else {
                $details[$status->getConnectionName()] = $status->getStatus();
            }
        }

        $message = sprintf(
            "Connection status summary: failing=%d working=%d unknown=%d",
            $this->getAttribute('failing'),
            $this->getAttribute('working'),
            $this->getAttribute('unknown')
        );

        $body = [
            'application' => AgaviConfig::get('core.app_name'),
            'status' => ($this->getAttribute('failing', 0) > 0) ? 'failing' : 'working',
            'message' => $message,
            'summary' => [
                'failing' => $this->getAttribute('failing'),
                'working' => $this->getAttribute('working'),
                'unknown' => $this->getAttribute('unknown')
            ],
            'connections' => $details
        ];

        return $body;
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
