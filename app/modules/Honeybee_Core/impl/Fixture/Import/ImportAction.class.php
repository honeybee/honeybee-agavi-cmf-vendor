<?php

use Honeygavi\App\Base\Action;

class Honeybee_Core_Fixture_ImportAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $service_locator = $this->getServiceLocator();
        $fixture_service = $service_locator->getFixtureService();
        $target_name = $request_data->getParameter('target');
        $fixture_name = $request_data->getParameter('fixture');

        try {
            $fixture = $fixture_service->import($target_name, $fixture_name);
        } catch (Exception $e) {
            $this->setAttribute('errors', $e->getMessage() . PHP_EOL);
            return 'Error';
        }

        $this->setAttribute('fixture', $fixture);

        return 'Success';
    }
}
