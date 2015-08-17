<?php

namespace Honeybee\FrameworkBinding\Agavi\Request;

use AgaviRequestDataHolder;

interface OutputtypesRequestDataHolderInterface
{
    public function hasOutputtype($name);

    public function isOutputtypeValueEmpty($name);

    public function &getOutputtype($name, $default = null);

    public function &getOutputtypes();

    public function getOutputtypeNames();

    public function setOutputtype($name, $value);

    public function setOutputtypes(array $headers);

    public function &removeOutputtype($name);

    public function clearOutputtypes();

    public function mergeOutputtypes(AgaviRequestDataHolder $other);
}
