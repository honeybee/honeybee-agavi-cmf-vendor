<?php

namespace Honeygavi\Ui\Filter;

use Trellis\Common\Collection\TypedMap;

class ListFilterMap extends TypedMap
{
    protected function getItemImplementor()
    {
        return ListFilterInterface::CLASS;
    }
}
