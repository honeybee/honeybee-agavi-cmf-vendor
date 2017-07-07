<?php

namespace Honeygavi\Ui\Filter;

interface ListFilterInterface
{
    public function getName();

    public function getCurrentValue();

    public function getAttribute();
}
