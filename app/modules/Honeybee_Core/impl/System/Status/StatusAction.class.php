<?php

use Honeygavi\Agavi\App\Base\Action;
use Honeygavi\Agavi\ShutdownListenerInterface;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

/**
 * Compile information about the application's status.
 */
class Honeybee_Core_System_StatusAction extends Action implements ShutdownListenerInterface
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->getContext()->addShutdownListener($this); // try to catch fatal errors while status checks run

        $status = Status::UNKNOWN;
        try {
            $connector_service = $this->getServiceLocator()->getConnectorService();
            $connections_report = $connector_service->getStatusReport()->toArray();
            if ($connections_report['status'] !== Status::FAILING) {
                $status = Status::WORKING;
            }
        } catch (Exception $e) {
            $this->logError('Error while getting system status:', $e);
            return 'Error';
        }

        $this->setAttribute('connections_report', $connections_report);
        $this->setAttribute('status', $status);

        $verbose = $request_data->getParameter('v', false) || $request_data->getParameter('verbose', false);
        $this->setAttribute('verbose', $verbose);

        $this->getContext()->removeShutdownListener($this);

        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Error';
    }

    /**
     * Set the "app_status.is_secure" setting to FALSE, when you want to make the status page accessible to EVERYONE
     * without requiring a login. Please be sure to secure the URL via other means (webserver, load balancer etc.)
     * when you set the above setting to false.
     *
     * @return bool true by default to require a login for the status page as it contains sensitive information
     */
    public function isSecure()
    {
        return AgaviConfig::get('app_status.is_secure', true);
    }

    /**
     * This handler will be called when a FATAL ERROR or similar appears while checking the status of the application.
     *
     * Usually you should return false to let other registered handlers continue as well.
     *
     * @see register_shutdown_function()
     * @see error_get_last()
     * @see Honeygavi\Agavi\Context
     *
     * @param array $error associative array with keys "type", "message", "file" and "line"
     *
     * @return boolean true to stop propagation of shutdown to other registered shutdown handlers
     */
    public function onShutdown($error)
    {
        // on CLI the headers are already sent, on HTTP stuff we try to set status code 500 on fatal errors
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        return false;
    }
}
