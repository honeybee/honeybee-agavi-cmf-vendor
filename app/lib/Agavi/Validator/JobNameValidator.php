<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

/**
 * Validator for console usage that asks for a valid transport name.
 */
class JobNameValidator extends ConsoleDialogValidator
{
    protected function validate()
    {
        $success = parent::validate();

        return $success;
    }

    /**
     * Adds only valid transport names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $job_map = $this->getContext()
            ->getServiceLocator()
            ->getJobService()
            ->getJobMap();

        $this->choices = [];
        foreach ($job_map->getSettings() as $name => $job) {
            $this->choices[] = $name;
        }
    }
}
