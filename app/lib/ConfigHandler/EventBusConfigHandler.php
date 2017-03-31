<?php

namespace Honeygavi\ConfigHandler;

use Honeybee\Model\Event\Subscription\EventFilter;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Common\Error\ConfigError;
use AgaviToolkit;

class EventBusConfigHandler extends BaseConfigHandler
{
    const DEFAULT_FILTER = EventFilter::CLASS;

    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/event_bus/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'bus');

        $transports = [];
        $subscriptions = [];

        foreach ($document->getConfigurationElements() as $configuration_node) {
            if ($event_bus_element = $configuration_node->getChild('event_bus')) {
                if ($transports_node = $event_bus_element->getChild('transports')) {
                    $transports = array_merge($transports, $this->parseTransports($transports_node));
                }
            }
        }

        $channels = [];
        foreach ($document->getConfigurationElements() as $configuration_node) {
            if ($event_bus_element = $configuration_node->getChild('event_bus')) {
                foreach ($event_bus_element->get('channels') as $channel_element) {
                    $channel_data = $this->parseChannel($channel_element, $transports);
                    if (isset($channels[$channel_data['name']])) {
                        $channels[$channel_data['name']]['subscriptions'] = array_merge(
                            $channels[$channel_data['name']]['subscriptions'],
                            $channel_data['subscriptions']
                        );
                    } else {
                        $channels[$channel_data['name']] = $channel_data;
                    }
                }
            }
        }

        $config_data = [ 'channels' => $channels, 'transports' => $transports ];
        $config_code = sprintf('return %s;', var_export($config_data, true));

        return $this->generate($config_code, $document->documentURI);
    }

    protected function parseTransports(AgaviXmlConfigDomElement $transports_element)
    {
        $transports = [];

        foreach ($transports_element->get('transport') as $transport_element) {
            $implementor = $transport_element->getChild('implementor')->getValue();
            $name = $transport_element->getAttribute('name');
            $settings = [];

            $settings_element = $transport_element->getChild('settings');
            if ($settings_element) {
                $settings = $this->parseSettings($settings_element);
            }

            if (!class_exists($implementor)) {
                throw new ConfigError('Unable to load transport implementor.');
            }

            $transports[$name] = [
                'name' => $name,
                'implementor' => $implementor,
                'settings' => $settings
            ];
        }

        return $transports;
    }

    protected function parseChannel(AgaviXmlConfigDomElement $channel_element, array $transports)
    {
        if ($channel_element->hasChild('subscriptions')) {
            $subscriptions_parent = $channel_element->getChild('subscriptions');
        } else {
            $subscriptions_parent = $channel_element;
        }

        return array(
            'name' => $channel_element->getAttribute('name'),
            'subscriptions' => $this->parseSubscriptions($subscriptions_parent, $transports)
        );
    }

    protected function parseSubscriptions(AgaviXmlConfigDomElement $subscriptions_parent, array $transports)
    {
        $subscriptions = [];

        foreach ($subscriptions_parent->get('subscription') as $subscription_element) {
            $transport = $subscription_element->getChild('transport')->getValue();
            if (!isset($transports[$transport])) {
                throw new ConfigError(
                    'Unable to resolve configured type ' . $transport . ' to local declaration.'
                    . PHP_EOL . 'Maybe a typo within the transport or subscription config?'
                );
            }

            if ($subscription_element->hasChild('filters')) {
                $filters_parent = $subscription_element->getChild('filters');
            } else {
                $filters_parent = $subscription_element;
            }
            $filters = $this->parseFilters($filters_parent);

            $event_handlers = [];
            foreach ($subscription_element->get('handlers') as $handler_element) {
                $event_handlers[] = [
                    'implementor' => $handler_element->getAttribute('implementor'),
                    'settings' => $this->parseSettings($handler_element)
                ];
            }

            $settings = [];
            $settings_element = $subscription_element->getChild('settings');
            if ($settings_element) {
                $settings = $this->parseSettings($settings_element);
            }

            $subscriptions[] = [
                'transport' => $transport,
                'filters' => $filters,
                'handlers' => $event_handlers,
                'settings' => $settings,
                'enabled' => AgaviToolkit::literalize(
                    $subscription_element->getAttribute('enabled', true)
                )
            ];
        }

        return $subscriptions;
    }

    protected function parseFilters(AgaviXmlConfigDomElement $filters_parent)
    {
        $filters = [];

        foreach ($filters_parent->get('filters') as $filter_element) {
            if ($filter_element->hasChild('settings')) {
                $settings_parent = $filter_element->getChild('settings');
            } else {
                $settings_parent = $filter_element;
            }

            $filters[] = [
                'implementor' => $filter_element->getAttribute('implementor', self::DEFAULT_FILTER),
                'settings' => $this->parseSettings($filter_element)
            ];
        }

        return $filters;
    }
}
