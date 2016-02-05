<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

/**
 * Text output format is ideal for monitoring system to use as text for emails etc.
 * thus we return HTTP status 500 for TEXT output format. With that a HTTP Status
 * 200 vs. 500 check can be used by e.g. icinga to determine the application's status.
 * The 500 error is not returned for the other output types as the report compilation
 * succeeded and thus HTTP status 200 is appropriate. Further on triggering a 500 error
 * could lead to default error pages of other infrastructure components that are in front
 * of this application (load balancers, web servers etc.).
 */
class Honeybee_Core_System_Status_StatusSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute('_title', 'Status');

        $this->setupHtml($request_data);

        $this->setAttribute('report_as_string', $this->getReportAsString($request_data));
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return json_encode(
            $this->prepareReport($request_data),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE
        );
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

    public function executeText(AgaviRequestDataHolder $request_data)
    {
        $report = $this->getReportAsString($request_data);

        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');

        // on failing status => text output format with 500 http status for easier monitoring/alerting
        if ($this->getAttribute('status') === Status::FAILING) {
            $this->getResponse()->setHttpStatusCode('500');
        }

        return $report;
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
                    json_encode(
                        $connection_status['details'],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE
                    )
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
}
