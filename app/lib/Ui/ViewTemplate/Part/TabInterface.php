<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

interface TabInterface extends NamedItemInterface
{
    public function getCss();

    public function getPanelList();
}
