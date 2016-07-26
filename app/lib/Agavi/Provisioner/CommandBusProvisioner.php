<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use AgaviContext;
use Auryn\Injector as DiContainer;
use Honeybee\Infrastructure\Command\Bus\CommandBusInterface;
use Honeybee\Infrastructure\Command\Bus\Subscription\LazyCommandSubscription;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;

class CommandBusProvisioner extends AbstractProvisioner
{
    const COMMAND_BUS_CONFIG_FILE = 'commands.xml';

    protected $prepare_executed;

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $command_bus_config = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::COMMAND_BUS_CONFIG_FILE,
            AgaviContext::getInstance()->getName()
        );

        $callback = function (
            CommandBusInterface $command_bus,
            DiContainer $di_container
        ) use (
            $command_bus_config
        ) {
            if (!$this->prepare_executed) {
                $this->prepareCommandBus($command_bus, $command_bus_config);
                $this->prepare_executed = true;
            }
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->prepare($service, $callback)
            ->share($service)
            ->alias(CommandBusInterface::CLASS, $service);
    }

    protected function prepareCommandBus(CommandBusInterface $command_bus, array $command_bus_config)
    {
        $built_transports = [];
        $di_container = $this->di_container;

        foreach ($command_bus_config['transports'] as $transport_name => $transport_config) {
            if (!isset($built_transports[$transport_name])) {
                $built_transports[$transport_name] = $this->buildTransport(
                    $transport_name,
                    $transport_config,
                    $command_bus
                );
            }
        }

        foreach ($command_bus_config['subscriptions'] as $transport_name => $subscription_config) {
            foreach ($subscription_config['commands'] as $command_type => $command_config) {
                $command_bus->subscribe(
                    $di_container->make(
                        LazyCommandSubscription::CLASS,
                        [
                            ':command_type' => $command_type,
                            ':command_transport' => $built_transports[$transport_name],
                            ':command_handler_callback' => function () use ($di_container, $command_config) {
                                return $di_container->make($command_config['handler']);
                            }
                        ]
                    )
                );
            }
        }
    }

    protected function buildTransport($transport_name, array $transport_config, CommandBusInterface $command_bus)
    {
        $transport_state = array(':name' => $transport_name, ':command_bus' => $command_bus);

        foreach ($transport_config['settings'] as $prop_name => $prop_value) {
            $transport_state[':' . $prop_name] = $prop_value;
        }

        return $this->di_container->make($transport_config['implementor'], $transport_state);
    }
}
