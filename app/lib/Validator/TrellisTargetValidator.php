<?php

namespace Honeygavi\Validator;

use Honeygavi\CodeGen\Trellis\TrellisTargetFinder;
use Honeygavi\Util\HoneybeeAgaviToolkit;

/**
 * Validator for console usage that asks for a valid module name.
 */
class TrellisTargetValidator extends ConsoleDialogValidator
{
    const ALL_TARGETS = 'all';

    /**
     * Validate value and provide a validation token for the input value.
     */
    protected function validate()
    {
        $success = parent::validate();

        return $success;
    }

    /**
     * Adds only valid target names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $this->finder = new TrellisTargetFinder();
        $location = HoneybeeAgaviToolkit::getTypeSchemaPath($this->getData('type'));

        $this->choices = array();
        foreach ($this->finder->findAll((array)$location) as $path) {
            $this->choices[] = sprintf(
                pathinfo($path->getFilename(), PATHINFO_FILENAME)
            );
        }

        $this->choices[] = self::ALL_TARGETS;
    }
}
