<?php

namespace Honeygavi\Ui\Filter;

use Honeybee\Common\Util\StringToolkit;

class ListFilter implements ListFilterInterface
{
    protected $name;

    protected $attribute;

    protected $current_value;

    public function __construct($name, $current_value, $attribute = null)
    {
        $this->name = $name;
        $this->current_value = $current_value;
        $this->attribute = $attribute;
    }

    public function getId()
    {
        return str_replace('.', '_', StringToolkit::asSnakeCase($this->getName()));
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAttribute()
    {
        return $this->attribute;
    }

    public function getCurrentValue()
    {
        return $this->current_value;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'attribute' => $this->getAttribute(),
            'current_value' => $this->getCurrentValue()
        ];
    }
}
