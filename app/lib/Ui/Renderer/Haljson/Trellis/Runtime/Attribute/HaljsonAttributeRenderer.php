<?php

namespace Honeybee\Ui\Renderer\Haljson\Trellis\Runtime\Attribute;

use DateTimeInterface;
use Honeybee\Ui\Renderer\AttributeRenderer;
use Trellis\Runtime\Attribute\AttributeValuePath;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;
use Trellis\Runtime\ValueHolder\ComplexValueInterface;

class HaljsonAttributeRenderer extends AttributeRenderer
{
    protected function doRender()
    {
        $value = null;

        if ($this->hasOption('value')) {
            return $this->getOption('value');
        }

        $value_path = $this->getOption('attribute_value_path');
        if (!empty($value_path)) {
            $value = AttributeValuePath::getAttributeValueByPath($this->getPayload('resource'), $value_path);
        } else {
            $value = $this->getPayload('resource')->getValue($this->attribute->getName());
        }

        if (is_object($value)) {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format(TimestampAttribute::FORMAT_ISO8601);
            } elseif ($value instanceof ComplexValueInterface) {
                $value = $value->toNative();
            } else {
                // unknown object, hopefully json serializable
            }
        }

        return $value;
    }
}
