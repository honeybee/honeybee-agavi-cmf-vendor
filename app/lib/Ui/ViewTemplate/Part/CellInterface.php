<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

interface CellInterface
{
    public function getCss();

    public function getGroupList();

    public function getGroup($name);
}
