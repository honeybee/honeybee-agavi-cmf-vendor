<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

class PanelList extends NamedItemList
{
    protected function getItemImplementor()
    {
        return PanelInterface::CLASS;
    }
}
