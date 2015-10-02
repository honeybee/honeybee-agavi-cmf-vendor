<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\History;

use AgaviRequestDataHolder;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\Workflow\Plugin\InteractionResult;

class HistorySuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $resource = $this->getAttribute('resource');
        $resource_type = $resource->getType();
        $events_data = [];
        foreach ($this->getHistoryData() as $event_data) {
            $events_data[] = [
                'data' => $event_data,
                'url' => $this->getContext()->getRouting()->gen(
                    'module.resource',
                    [ 'revision' => $event_data['seq_number'], 'resource' => $resource ]
                )
            ];
        }

        $this->setSubheaderActivities($request_data);

        $this->setAttribute('events_data', $events_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setContent(
            json_encode($this->getHistoryData())
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        foreach ($this->getAttribute('domain_events') as $domain_event) {
            printf(
                'Nr. %d%s',
                $domain_event->getSeqNumber() . PHP_EOL,
                print_r($domain_event->toArray(), true) . PHP_EOL
            );
        }
    }

    protected function getHistoryData()
    {
        $events_data = [];
        foreach ($this->getAttribute('domain_events') as $domain_event) {
            $events_data[] = $domain_event->toArray();
        }

        return $events_data;
    }
}
