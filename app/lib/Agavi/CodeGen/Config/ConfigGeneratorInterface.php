<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

interface ConfigGeneratorInterface
{
    public function generate($name, array $affected_paths);
}
