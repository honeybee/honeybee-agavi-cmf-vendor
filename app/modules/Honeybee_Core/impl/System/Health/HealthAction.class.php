<?php

use Honeygavi\App\Base\Action;
use Honeygavi\ShutdownListenerInterface;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

/**
 * Compile information about the application's status and return success or error without further info.
 * The connections are only actually used for checks when verbose request parameter is set (?v=1).
 */
class Honeybee_Core_System_HealthAction extends Action implements ShutdownListenerInterface
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $this->getContext()->addShutdownListener($this);

        $verbose = $request_data->getParameter('v', false) || $request_data->getParameter('verbose', false);
        $this->setAttribute('verbose', $verbose);

        $status = Status::UNKNOWN;
        try {
            $connector_service = $this->getServiceLocator()->getConnectorService();
            if ($verbose) {
                $connections_report = $connector_service->getStatusReport()->toArray();
                if ($connections_report['status'] === Status::FAILING) {
                    $status = Status::FAILING;
                } elseif ($connections_report['status'] === Status::WORKING) {
                    $status = Status::WORKING;
                }
            } else {
                $status = Status::UNKNOWN; // w/o "?v=1" we assume connectors are probably working and return 200
            }
        } catch (Throwable $e) {
            $this->logError('Error while getting system status:', $e);
            return 'Error';
        }

        $this->setAttribute('status', $status);

        $this->getContext()->removeShutdownListener($this);

        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Error';
    }

    /**
     * Set the "app_health.is_secure" setting to TRUE when you want to prevent unauthenticated access of that page.
     *
     * @return boolean
     */
    public function isSecure()
    {
        return AgaviConfig::get('app_health.is_secure', false);
    }

    /**
     * This handler will be called when a FATAL ERROR or similar appears while checking the status of the application.
     *
     * Usually you should return false to let other registered handlers continue as well.
     *
     * @see register_shutdown_function()
     * @see error_get_last()
     * @see Honeygavi\Context
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
