<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

class FieldList extends NamedItemList
{
    protected function getItemImplementor()
    {
        return FieldInterface::CLASS;
    }
}
