<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

use Trellis\Common\Collection\TypedList;
use Trellis\Common\Collection\UniqueValueInterface;

class RowList extends TypedList implements UniqueValueInterface
{
    protected function getItemImplementor()
    {
        return RowInterface::CLASS;
    }
}
