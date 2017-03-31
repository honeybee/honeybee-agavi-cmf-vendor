<?php

namespace Honeygavi\Agavi\Validator;

use Honeygavi\Agavi\CodeGen\Skeleton\HoneybeeModuleFinder;

/**
 * Validator for console usage that asks for a valid module name.
 */
class ModuleNameValidator extends ConsoleDialogValidator
{
    /**
     * Validate value and provide a validation token for the input value.
     */
    protected function validate()
    {
        $success = parent::validate();

        $module_name = $this->data;

        $module = $this->finder->findByName($module_name);

        $module_settings = parse_ini_file($module->getPathName() . DIRECTORY_SEPARATOR . 'module.ini');
        if ($module_settings === false) {
            $this->throwError();
            return false;
        }

        $target_path = $module->getPathName();
        if ($this->hasParameter('relative_target_path')) {
            $target_path .= DIRECTORY_SEPARATOR . $this->getParameter('relative_target_path');
        }

        $this->export($target_path, 'target_path');
        $this->export($module_settings['vendor'], 'vendor');
        $this->export($module_settings['package'], 'package');

        return $success;
    }

    /**
     * Adds only valid module names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $this->finder = new HoneybeeModuleFinder();

        $this->choices = array();
        foreach ($this->finder->findAll() as $path) {
            $this->choices[] = $path->getFilename();
        }
    }
}
