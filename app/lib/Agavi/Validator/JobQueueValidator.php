<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

/**
 * Validator for console usage that asks for a valid queue name.
 */
class JobQueueValidator extends ConsoleDialogValidator
{
    protected function validate()
    {
        $success = parent::validate();

        return $success;
    }

    /**
     * Adds only valid queue names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $job_map = $this->getContext()
            ->getServiceLocator()
            ->getJobService()
            ->getJobMap();

        $choices = [];
        foreach ($job_map->getSettings() as $name => $job) {
            $choices[] = $job->get('settings')->get('queue');
        }
        $this->choices = array_unique($choices);
    }
}
