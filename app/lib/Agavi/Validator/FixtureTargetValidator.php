<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Infrastructure\Fixture\FixtureTargetInterface;

/**
 * Validator for console usage that asks for a valid fixture target name.
 */
class FixtureTargetValidator extends ConsoleDialogValidator
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

        $this->choices = $this->getContext()
            ->getServiceLocator()
            ->getFixtureService()
            ->getFixtureTargetMap()
            ->filter(function(FixtureTargetInterface $fixture_target) {
                return $fixture_target->isActivated();
            })
            ->getKeys();
    }
}
