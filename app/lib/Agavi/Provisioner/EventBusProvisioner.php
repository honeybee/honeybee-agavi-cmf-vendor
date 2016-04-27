<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use AgaviContext;
use Auryn\Injector as DiContainer;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Event\Bus\Channel\Channel;
use Honeybee\Infrastructure\Event\Bus\Channel\ChannelMap;
use Honeybee\Infrastructure\Event\Bus\EventBusInterface;
use Honeybee\Infrastructure\Event\Bus\Subscription\EventFilter;
use Honeybee\Infrastructure\Event\Bus\Subscription\EventFilterList;
use Honeybee\Infrastructure\Event\Bus\Subscription\EventSubscription;
use Honeybee\Infrastructure\Event\Bus\Subscription\LazyEventSubscription;
use Honeybee\Infrastructure\Event\EventHandlerList;
use Honeybee\ServiceDefinitionInterface;
use Psr\Log\LoggerInterface;

class EventBusProvisioner extends AbstractProvisioner
{
    const EVENT_BUS_CONFIG_FILE = 'events.xml';

    protected $prepare_executed;

    public function build(
        ServiceDefinitionInterface $service_definition,
        SettingsInterface $provisioner_settings = null
    ) {
        $event_bus_config = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::EVENT_BUS_CONFIG_FILE,
            AgaviContext::getInstance()->getName()
        );

        $channel_map = new ChannelMap();
        foreach ($event_bus_config['channels'] as $channel_name => $channel_config) {
            $channel_map->setItem($channel_name, new Channel($channel_name));
        }

        $that = $this;
        $callback = function (
            EventBusInterface $event_bus,
            DiContainer $di_container
        ) use (
            $event_bus_config,
            $that
        ) {
            if (!$that->prepare_executed) {
                $that->prepareEventBus($event_bus, $event_bus_config);
                $that->prepare_executed = true;
            }
        };

        $service = $service_definition->getClass();
        $state = [ ':channel_map' => $channel_map ];

        $this->di_container
            ->define($service, $state)
            ->prepare($service, $callback)
            ->share($service)
            ->alias(EventBusInterface::CLASS, $service);
    }

    protected function prepareEventBus(EventBusInterface $event_bus, array $event_bus_config)
    {
        $built_transports = [];

        foreach ($event_bus_config['channels'] as $channel_name => $channel_config) {
            foreach ($channel_config['subscriptions'] as $subscription_config) {
                $event_handlers_callback = function () use ($subscription_config) {
                    return $this->buildEventHandlers($subscription_config['handlers']);
                };

                $event_filters_callback = function () use ($subscription_config) {
                    return $this->buildEventFilters($subscription_config['filters']);
                };

                $event_transport_callback = function () use (
                    $subscription_config,
                    $event_bus_config,
                    $event_bus,
                    &$built_transports
                ) {
                    $transport_name = $subscription_config['transport'];
                    if (!isset($built_transports[$transport_name])) {
                        $built_transports[$transport_name] = $this->buildTransport(
                            $event_bus_config['transports'],
                            $subscription_config['transport'],
                            $event_bus
                        );
                    }

                    return $built_transports[$transport_name];
                };

                $event_bus->subscribe(
                    $channel_name,
                    new LazyEventSubscription(
                        $event_handlers_callback,
                        $event_filters_callback,
                        $event_transport_callback,
                        new Settings($subscription_config['settings']),
                        $subscription_config['enabled']
                    )
                );
            }
        }
    }

    protected function buildTransport(array $transport_configs, $transport_name, EventBusInterface $event_bus)
    {
        if (!isset($transport_configs[$transport_name])) {
            throw new RuntimeException(
                sprintf('Unable to resolve config for transport: %s', $transport_name)
            );
        }

        $transport_config = $transport_configs[$transport_name];
        $transport_state = [
            ':name' => $transport_name,
            ':event_bus' => $event_bus
        ];
        foreach ($transport_config['settings'] as $key => $value) {
            $transport_state[':' . $key] = $value;
        }

        return $this->di_container->make($transport_config['implementor'], $transport_state);
    }

    protected function buildEventHandlers(array $handler_configs)
    {
        $event_handlers = new EventHandlerList();

        foreach ($handler_configs as $handler_config) {
            $event_handlers->addItem(
                $this->di_container->make(
                    $handler_config['implementor'],
                    [ ':config' => new ArrayConfig($handler_config['settings']) ]
                )
            );
        }

        return $event_handlers;
    }

    protected function buildEventFilters(array $filter_configs)
    {
        // @todo make filter implementor configurable.
        $filter_implementor = EventFilter::class;
        $event_filters = new EventFilterList();

        foreach ($filter_configs as $filter_config) {
            $event_filters->addItem(
                $this->di_container->make(
                    $filter_implementor,
                    [ ':settings' => new Settings($filter_config['settings']) ]
                )
            );
        }

        return $event_filters;
    }
}
