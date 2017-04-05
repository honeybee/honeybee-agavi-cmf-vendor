<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

interface GroupInterface extends NamedItemInterface
{
    public function getCss();

    public function getFieldList();

    public function getField($name);
}
