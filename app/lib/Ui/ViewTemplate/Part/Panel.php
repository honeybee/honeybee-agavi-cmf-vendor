<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

use Trellis\Common\BaseObject;

class Panel extends BaseObject implements PanelInterface
{
    protected $name;

    protected $css;

    protected $row_list;

    public function __construct($name, RowList $row_list, $css = '')
    {
        $this->name = $name;
        $this->row_list = $row_list;
        $this->css = $css;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCss()
    {
        return $this->css;
    }

    public function getRowList()
    {
        return $this->row_list;
    }
}
