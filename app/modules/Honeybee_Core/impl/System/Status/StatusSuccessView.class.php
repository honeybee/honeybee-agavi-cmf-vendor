<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

class Honeybee_Core_System_Status_StatusSuccessView extends View
{
    const JSON_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE;

    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('_title', 'Status');

        $this->setupHtml($request_data);

        $this->setAttribute('report_as_string', $this->getReportAsString($request_data));
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return json_encode($this->prepareReport($request_data), self::JSON_OPTIONS);
    }

    public function executeXml(AgaviRequestDataHolder $request_data)
    {
        $report = $this->prepareReport($request_data);

        $xml = new XmlWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('application');
        $xml->writeAttribute('name', $report['application']);

        $xml->startElement('status');
        $xml->text($report['status']);
        $xml->endElement();

        $connections = $report['connections'];
        $xml->startElement('connections');
        foreach ($connections['stats'] as $name => $value) {
            $xml->writeAttribute($name, $value);
        }
        $xml->writeElement('status', $connections['status']);
        $xml->startElement('stats');
        foreach ($connections['stats'] as $name => $value) {
            $xml->writeElement($name, $value);
        }
        $xml->endElement(); // connections/stats
        foreach ($connections['details'] as $name => $value) {
            $xml->startElement('connection');
            $xml->writeAttribute('name', $name);
            if (is_array($value)) {
                $this->array2xml($value, $xml);
            } else {
                $xml->writeCData((string)$value);
            }
            $xml->endElement();
        }
        $xml->endElement(); // connections

        $xml->endElement(); // application

        $xml->endDocument();

        return $xml->outputMemory();
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $report = $this->getReportAsString($request_data);

        if ($this->getAttribute('status') === Status::FAILING) {
            return $this->cliError($report);
        }

        return $this->cliMessage($report);
    }

    protected function getReportAsString($request_data)
    {
        $verbose = $this->getAttribute('verbose', false);
        $report = $this->prepareReport($request_data);

        $appstatus = sprintf("Status for application '%s': %s", $report['application'], $report['status']);

        $message = $appstatus . "\n\nConnections:\n\n";

        foreach ($report['connections']['details'] as $connection_name => $connection_status) {
            if (is_array($connection_status)) {
                $message .= sprintf(
                    "- %s = %s (%s) %s\n",
                    $connection_status['connection_name'],
                    $connection_status['status'],
                    $connection_status['implementor'],
                    json_encode($connection_status['details'], self::JSON_OPTIONS)
                );
            } else {
                $message .= sprintf(
                    "- %s = %s\n",
                    $connection_name,
                    $connection_status
                );
            }
        }

        $message .= sprintf(
            "\nConnections status: %s (failing=%d working=%d unknown=%d of %d overall)\n\n",
            $report['connections']['status'],
            $report['connections']['stats']['failing'],
            $report['connections']['stats']['working'],
            $report['connections']['stats']['unknown'],
            $report['connections']['stats']['overall']
        );

        $message .= $appstatus;

        return $message;
    }

    protected function prepareReport(AgaviRequestDataHolder $request_data)
    {
        $verbose = $this->getAttribute('verbose', false);
        $connections_report = $this->getAttribute('connections_report', []);

        $conninfo = $connections_report;
        foreach ($connections_report['details'] as $name => $conn) {
            // remove verbose connection info details when not explicitely asked for
            if (!$verbose) {
                $conninfo['details'][$name] = $conn['status'];
            }
        }

        $report = [
            'application' => AgaviConfig::get('core.app_name'),
            'status' => $this->getAttribute('status', Status::UNKNOWN),
            'connections' => $conninfo
        ];

        $this->setAttribute('report', $report);

        return $report;
    }

    protected function array2xml($data, XMLWriter $xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[0])) {
                $xml->startElement($key);
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $this->array2xml($item, $xml);
                    } else {
                        $xml->writeElement($key, (string)$item);
                    }
                }
                $xml->endElement();
            } elseif (is_array($value)) {
                $xml->startElement($key);
                $this->array2xml($value, $xml);
                $xml->endElement();
                continue;
            }

            if (!is_array($value)) {
                if (is_numeric($key)) {
                    $xml->writeElement('item_'.$key, (string)$value);
                } else {
                    $xml->writeElement($key, (string)$value);
                }
            }
        }
    }
/*
    public function executeBinary(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent('status?');
    }

    public function executePdf(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent('status?');
    }
 */
}
