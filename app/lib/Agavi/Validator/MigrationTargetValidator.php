<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Infrastructure\Migration\MigrationTargetInterface;

/**
 * Validator for console usage that asks for a valid migration target name.
 * @see migration.xml files
 */
class MigrationTargetValidator extends ConsoleDialogValidator
{
    const ALL_TARGETS = 'all';

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
            ->getMigrationService()
            ->getMigrationTargetMap()
            ->filter(function(MigrationTargetInterface $fixture_target) {
                return $fixture_target->isActivated();
            })
            ->getKeys();

        $this->choices[] = self::ALL_TARGETS;
    }
}
