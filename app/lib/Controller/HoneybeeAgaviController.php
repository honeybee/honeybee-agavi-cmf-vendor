<?php

namespace Honeygavi\Controller;

use AgaviController;

class HoneybeeAgaviController extends AgaviController
{
    public function hasOutputType($name = null)
    {
        return array_key_exists($name, $this->outputTypes);
    }

    public function getOutputTypeNames()
    {
        return array_keys($this->outputTypes);
    }

    public function getOutputTypes()
    {
        return $this->outputTypes;
    }
}
