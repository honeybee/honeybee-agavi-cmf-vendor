<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

interface PanelInterface extends NamedItemInterface
{
    public function getCss();

    public function getRowList();
}
