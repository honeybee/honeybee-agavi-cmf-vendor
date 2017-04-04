<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

class GroupList extends NamedItemList
{
    protected function getItemImplementor()
    {
        return GroupInterface::CLASS;
    }
}
