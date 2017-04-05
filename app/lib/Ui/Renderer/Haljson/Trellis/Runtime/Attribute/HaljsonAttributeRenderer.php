<?php

namespace Honeygavi\Ui\Renderer\Haljson\Trellis\Runtime\Attribute;

use DateTimeInterface;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeygavi\Ui\Renderer\AttributeRenderer;
use Trellis\Runtime\Attribute\AttributeValuePath;
use Trellis\Runtime\Attribute\ListAttribute;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\ValueHolder\ComplexValueInterface;

/**
 * Fallback haljson renderer for attributes that is usually
 * used when there no attribute type specific renderers.
 */
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
                $value = $value->toArray();
            } else {
                // unknown object, hopefully json serializable
            }
        }

        if ($this->attribute instanceof ListAttribute) {
            if (is_array($value)) {
                return array_map(function($elm) {
                    if ($elm instanceof ComplexValueInterface) {
                        return $elm->toArray();
                    }
                    return $elm;
                }, $value);
            } elseif ($value instanceof EntityList) {
                $rendered_entities = [];
                foreach ($value as $entity) {
                    $rendered_entities[] = $this->renderer_service->renderSubject(
                        $entity,
                        $this->output_format,
                        new ArrayConfig($this->getOptions()) // todo don't pass everything on?
                    );
                }
                return $rendered_entities;
            } else {
                return $value;
            }
        }

        return $value;
    }
}
