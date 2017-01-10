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

    public function executeHaljson(AgaviRequestDataHolder $request_data)
    {
        $service_locator = $this->getContext()->getServiceLocator();

        $json = [
            '_links' => [
                'self' => [ 'href' => $this->routing->gen(null) ]
            ],
            'message' => $this->translation_manager->_('Welcome')
        ];

        $navigation_service = $this->getServiceLocator()->getNavigationService();
        $navigation = $navigation_service->getNavigation($this->getNavigationName());
        $rendered_navigation = $this->renderSubject($navigation);

        $json['_links'] = array_replace_recursive($json['_links'], $rendered_navigation);

        return json_encode($json, self::JSON_OPTIONS);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getContainer()->getResponse()->setContent(
            json_encode([ 'message' => $this->translation_manager->_('Welcome') ])
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $msg = 'Welcome to the Honeybee CLI Interface.' . PHP_EOL;

        $this->getResponse()->setContent($msg);
    }
}
