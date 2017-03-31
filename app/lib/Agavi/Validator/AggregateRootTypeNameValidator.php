<?php

namespace Honeygavi\Agavi\Validator;

class AggregateRootTypeNameValidator extends ConsoleDialogValidator
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
            ->getAggregateRootTypeMap()
            ->getKeys();
    }
}
