<?php

namespace Honeygavi\Ui\ViewTemplate\Part;

use Trellis\Common\BaseObject;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\ConfigInterface;

class Field extends BaseObject implements FieldInterface
{
    protected $name;
    protected $config;

    public function __construct($name, ConfigInterface $config = null)
    {
        $this->config = $config ?: new ArrayConfig([]);
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCss()
    {
        return $this->config->get('css', '');
    }

    public function getTemplate()
    {
        return $this->config->get('template');
    }

    public function hasTemplate()
    {
        return $this->config->has('template');
    }

    public function getRenderer()
    {
        return $this->config->get('renderer');
    }

    public function hasRenderer()
    {
        return $this->config->has('renderer');
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getSetting($name, $default = null)
    {
        return $this->config->get($name, $default);
    }
}
