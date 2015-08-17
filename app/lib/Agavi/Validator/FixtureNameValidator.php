<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

/**
 * Validator for console usage that asks for a valid fixture target name.
 */
class FixtureNameValidator extends ConsoleDialogValidator
{
    protected function validate()
    {
        $success = parent::validate();

        return $success;
    }

    /**
     * Adds only valid migration target names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $target_name = $this->getData('target');
        $this->choices = [];

        $fixtures = $this->getContext()
            ->getServiceLocator()
            ->getFixtureService()
            ->getFixtureList($target_name);

        foreach ($fixtures as $fixture) {
            $this->choices[] = sprintf('%s:%s', $fixture->getVersion(), $fixture->getName());
        }
    }
}
