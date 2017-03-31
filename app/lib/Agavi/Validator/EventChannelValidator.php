<?php

namespace Honeygavi\Agavi\Validator;

/**
 * Validator for console usage that asks for a valid event-bus channel name.
 * @see events.xml files
 */
class EventChannelValidator extends ConsoleDialogValidator
{
    protected function validate()
    {
        $success = parent::validate();

        return $success;
    }

    /**
     * Adds only valid channel-names target names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $this->choices = $this->getContext()
            ->getServiceLocator()
            ->getEventBus()
            ->getChannels()
            ->getKeys();
    }
}
