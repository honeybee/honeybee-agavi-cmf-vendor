<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Resource\History;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class HistorySuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $resource = $this->getAttribute('resource');

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

    public function executeHaljson(AgaviRequestDataHolder $request_data)
    {
        $activity_service = $this->getServiceLocator()->getActivityService();

        $resource = $this->getAttribute('resource');
        $resource_type = $resource->getType();

        $curie = $this->getCurieName();
        $curies = $this->getCuries();

        $links = array_merge([], $curies);

        $tm = $this->translation_manager;
        $td = $resource_type->getPrefix() . '.views';

        $domain_events = $this->getAttribute('domain_events');

        $json = [
            'title' => $tm->_('resource.history.page_title', $td),
            'resource_type' => $resource_type->getPrefix(),
            'resource_type_name' => $tm->_($resource_type->getName(), $td),
            'number_of_events' => count($domain_events),
            '_embedded' => [],
        ];

        $activity = $activity_service->getActivity('default_resource_activities', 'that_revision');

        foreach ($domain_events as $domain_event) {
            $id = $domain_event->getType() . '@' . $domain_event->getSeqNumber();
            $json['_embedded'][$id] = array_merge(
                [
                    'type' => $domain_event->getType(),
                    'seq_number' => $domain_event->getSeqNumber(),
                    'iso_date' => $domain_event->getIsoDate(),
                    'changes' => count($domain_event->getData()),
                    'embedded_events' => count($domain_event->getEmbeddedEntityEvents()),
                    'issuer' => $domain_event->getMetadata()['user'],
                    'uuid' => $domain_event->getUuid(),
                    // 'aggregate_root_type' => $domain_event->getAggregateRootType(),
                    // 'aggregate_root_identifier' => $domain_event->getAggregateRootIdentifier(),

                ],
                [
                    '_links' => [
                        "$curie:default_resource_activities~that_revision" =>
                            $this->renderSubject(
                                $activity,
                                [
                                    'curie' => $curie,
                                    'additional_url_parameters' => [
                                        'resource' => $resource,
                                        'revision' => $domain_event->getSeqNumber()
                                    ]
                                ]
                            ),
                    ]
                ]
            );
        }

        $json = array_replace_recursive($json, [ '_links' => $links ]);

        return json_encode($json, self::JSON_OPTIONS);
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
