<?php

namespace Honeygavi\Validator;

/**
 * Validator for console usage that asks for a valid role id.
 */
class RoleDialogValidator extends ConsoleDialogValidator
{
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

        $this->choices = [];

        foreach ($this->getContext()->getUser()->getAvailableRoles() as $role_id) {
            $this->choices[] = $role_id;
        }
    }
}
