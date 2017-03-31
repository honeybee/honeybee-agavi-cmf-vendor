<?php

namespace Honeygavi\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Common\Error\ConfigError;

class CommandBusConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/command_bus/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'bus');

        $transports = array();
        $subscriptions = array();

        foreach ($document->getConfigurationElements() as $configuration_node) {
            if ($command_bus_element = $configuration_node->getChild('command_bus')) {
                if ($transports_node = $command_bus_element->getChild('transports')) {
                    $transports = array_merge($transports, $this->parseTransports($transports_node));
                }
            }
        }

        foreach ($document->getConfigurationElements() as $configuration_node) {
            if ($command_bus_element = $configuration_node->getChild('command_bus')) {
                if ($subscriptions_node = $command_bus_element->getChild('subscriptions')) {
                    $subscriptions = array_replace_recursive(
                        $subscriptions,
                        $this->parseSubscriptions($subscriptions_node, $transports)
                    );
                }
            }
        }

        $config_data = array('subscriptions' => $subscriptions, 'transports' => $transports);
        $config_code = sprintf('return %s;', var_export($config_data, true));

        return $this->generate($config_code, $document->documentURI);
    }

    protected function parseTransports(AgaviXmlConfigDomElement $transports_element)
    {
        $transports = array();

        foreach ($transports_element->get('transport') as $transport_element) {
            $implementor = $transport_element->getChild('implementor')->getValue();
            $name = $transport_element->getAttribute('name');
            $settings = array();

            $settings_element = $transport_element->getChild('settings');
            if ($settings_element) {
                $settings = $this->parseSettings($settings_element);
            }

            if (!class_exists($implementor)) {
                throw new ConfigError('Unable to load transport implementor.');
            }
            $transports[$name] = array(
                'name' => $name,
                'implementor' => $implementor,
                'settings' => $settings
            );
        }

        return $transports;
    }

    protected function parseSubscriptions(AgaviXmlConfigDomElement $subscriptions_element, array $transports)
    {
        $subscriptions = array();

        foreach ($subscriptions_element->get('subscription') as $subscription_element) {
            $transport = $subscription_element->getAttribute('transport');
            if (!isset($transports[$transport])) {
                throw new ConfigError(
                    'Unable to resolve configured type to local declaration.'
                    . PHP_EOL . 'Maybe a typo within the transport or subscription config?'
                );
            }

            $commands = array();
            foreach ($subscription_element->getChild('commands')->get('command') as $command_element) {
                $handler = null;
                $handler_element = $command_element->getChild('handler');
                if ($handler_element) {
                    $handler = $handler_element->getValue();
                }

                $command_type = $command_element->getAttribute('type');
                $commands[$command_type] = array(
                    'type' => $command_element->getAttribute('type'), 'handler' => $handler
                );
            }

            $subscriptions[$transport] = array(
                'transport' => $transport,
                'commands' => $commands
            );
        }

        return $subscriptions;
    }
}
