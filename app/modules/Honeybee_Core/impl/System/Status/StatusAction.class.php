<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Infrastructure\DataAccess\Connector\Status;

class Honeybee_Core_System_StatusAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        try {
            $infos = $this->getConnectorStatus();
            ksort($infos);
        } catch (Exception $e) {
            $this->logFatal('Error while getting system status:', $e);
            return 'Error';
        }

        $failing = 0;
        $unknown = 0;
        $working = 0;
        foreach ($infos as $name => $status) {
            if ($status->isFailing()) {
                $failing++;
            } elseif ($status->isWorking()) {
                $working++;
            } else {
                $unknown++;
            }
        }

        $this->setAttribute('infos', $infos);
        $this->setAttribute('failing', $failing);
        $this->setAttribute('working', $working);
        $this->setAttribute('unknown', $unknown);

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

    protected function getConnectorStatus()
    {
        $connector_service = $this->getServiceLocator()->getConnectorService();
        $connector_map = $connector_service->getConnectorMap();

        $infos = [];
        foreach ($connector_map as $name => $connector) {
            try {
                $infos[$name] = $connector->getStatus();
            } catch (Exception $e) {
                $infos[$name] = Status::failing(
                    $connector,
                    [ 'message' => 'Exception on getStatus(): ' . $e->getMessage() ]
                );
                $this->logError('Error while getting status of connection "' . $name . '":', $e);
            }
        }

        return $infos;
    }
}
