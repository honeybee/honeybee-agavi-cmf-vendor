<?php

namespace Honeygavi\Agavi\Validator;

use AgaviConfig;
use AgaviValidator;

/**
 * Accepts a folder name that must match the 'format' regular expression and
 * must not be readable (when including the 'prefix').
 */
class FolderMissingValidator extends AgaviValidator
{
    protected function validate()
    {
        if ($this->hasMultipleArguments()) {
            $this->throwError('multiple_arguments');
            return false;
        }

        $folder_name = $this->getData($this->getArgument());

        if (!is_scalar($folder_name)) {
            $this->throwError();
            return false;
        }

        if (!preg_match($this->getParameter('format'), $folder_name, $matches)) {
            $this->throwError('format');
            return false;
        }

        $folder = $this->getParameter('prefix', AgaviConfig::get('core.cms_dir')) . DIRECTORY_SEPARATOR . $folder_name;

        if (is_readable($folder)) {
            $this->throwError('readable');
            return false;
        }

        $this->export($folder_name, $this->getParameter('export', $this->getArgument()));

        return true;
    }
}
