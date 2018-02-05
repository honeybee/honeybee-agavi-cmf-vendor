<?php

namespace Honeygavi\Ui\ViewTemplate;

use Honeybee\Common\Error\RuntimeError;
use Trellis\Common\BaseObject;

class ViewTemplatesContainer extends BaseObject implements ViewTemplatesContainerInterface
{
    protected $scope;

    protected $view_template_map;

    public function getScope()
    {
        return $this->scope;
    }

    public function getViewTemplateMap()
    {
        return $this->view_template_map;
    }

    public function getViewTemplateByName($name)
    {
        if (empty($name) || !$this->view_template_map->hasKey($name)) {
            throw new RuntimeError(
                sprintf(
                    'ViewTemplate of name "%s" not found in view_templates container with scope "%s".',
                    $name,
                    $this->scope
                )
            );
        }

        return $this->view_template_map->getItem($name);
    }

    protected function setViewTemplateMap(ViewTemplateMap $view_template_map)
    {
        $this->view_template_map = $view_template_map;
    }
}
