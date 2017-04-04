<?php

namespace Honeygavi\CodeGen\Config;

interface ConfigGeneratorInterface
{
    public function generate($name, array $affected_paths);
}
