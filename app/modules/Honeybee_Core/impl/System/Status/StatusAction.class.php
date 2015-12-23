<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

class Honeybee_Core_System_StatusAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
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

        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
