<?php

namespace Honeygavi\Agavi\Validator;

use Honeygavi\Agavi\CodeGen\Skeleton\SkeletonFinder;

/**
 * Validator for console usage that asks for a valid skeleton name. A skeleton
 * is a folder in the dev/templates locations that is used for code generation.
 */
class SkeletonNameValidator extends ConsoleDialogValidator
{
    /**
     * Validate value and provide a validation token for the input value.
     */
    protected function validate()
    {
        $success = parent::validate();

        $token_name = sprintf('skeleton_name_%s', $this->data);

        $this->getDependencyManager()->addDependTokens(array($token_name), $this->curBase);

        return $success;
    }

    /**
     * Adds only valid skeleton names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $finder = new SkeletonFinder();

        $this->choices = array();
        foreach ($finder->findAll() as $skeleton_path) {
            $this->choices[] = $skeleton_path->getFilename();
        }
    }
}
