<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviConfigCache;
use AgaviConfig;
use AgaviContext;
use Honeybee\FrameworkBinding\Agavi\Provisioner\EventBusProvisioner;

/**
 * Validator for console usage that asks for a valid exchange name.
 */
class ExchangeNameValidator extends ConsoleDialogValidator
{
    protected function validate()
    {
        $success = parent::validate();

        return $success;
    }

    /**
     * Adds only valid exchange names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();
        $exchange_name = $this->getData('exchange');

        $event_bus_config = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . EventBusProvisioner::EVENT_BUS_CONFIG_FILE,
            AgaviContext::getInstance()->getName()
        );

        $this->choices = [];
        foreach ($event_bus_config['transports'] as $transport) {
            if (isset($transport['settings']['exchange'])) {
                $this->choices[] = $transport['settings']['exchange'];
            }
        }
    }
}
