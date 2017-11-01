<?php

namespace Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\Infrastructure\Config\Settings;
use Trellis\Runtime\Attribute\Attribute;

class ListFilter implements ListFilterInterface
{
    protected $name = '';

    protected $current_value = null;

    protected $attribute = null;

    protected $settings = [];

    public function __construct(
        $name,
        $current_value = null,
        Attribute $attribute = null,
        $settings = []
    ) {
        if (empty(trim($name))) {
            throw new RuntimeError('Filter name cannot be empty.');
        }
        // 'config_key' is to be used as identifier in configurations like translations, render-configs, etc.
        $settings += [ 'config_key' => StringToolkit::asSnakeCase(str_replace('.', '_', $name)) ];

        $this->name = $name;
        $this->current_value = $current_value;
        $this->attribute = $attribute;
        $this->settings = new Settings($settings);
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

    public function getSettings()
    {
        return $this->settings;
    }

    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'attribute' => $this->getAttribute(),
            'current_value' => $this->getCurrentValue(),
            'settings' => $this->settings->toArray()
        ];
    }
}
