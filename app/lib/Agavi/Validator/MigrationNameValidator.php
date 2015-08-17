<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Model\Aggregate\AggregateRootType;

/**
 * Validator for console usage that asks for a valid migration name.
 */
class MigrationNameValidator extends ConsoleDialogValidator
{
    const NONE_MIGRATION = 'none';

    protected function validate()
    {
        $success = parent::validate();

        return $success;
    }

    /**
     * Adds only valid migration names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $type_name = $this->getData('type')->getPackagePrefix();
        $this->choices = [];

        $migrations = $this->getContext()
            ->getServiceLocator()
            ->getMigrationService()
            ->getMigrationList($type_name . '::migration::view_store');

        foreach ($migrations as $migration) {
            $this->choices[] = sprintf('%s:%s', $migration->getVersion(), $migration->getName());
        }

        $this->choices[] = self::NONE_MIGRATION;
    }
}
