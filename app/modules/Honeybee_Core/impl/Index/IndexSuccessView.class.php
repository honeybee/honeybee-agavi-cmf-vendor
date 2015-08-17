<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Index_IndexSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $routing = $this->getContext()->getRouting();

        $arts = [];
        $service_locator = $this->getContext()->getServiceLocator();
        foreach ($service_locator->getAggregateRootTypeMap() as $art) {
            $arts[$art->getPrefix()] = [
                'collection_url' => $routing->gen('module.collection', [ 'module' => $art ])
            ];
        }

        $this->setAttribute('aggregate_root_types', $arts);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getContainer()->getResponse()->setContent(
            json_encode(
                array(
                    'result' => 'error',
                    'message' => 'Welcome to Honeybee.'
                )
            )
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $msg = 'Welcome to the Honeybee CLI Interface.' . PHP_EOL;

        $this->getResponse()->setContent($msg);
    }
}
