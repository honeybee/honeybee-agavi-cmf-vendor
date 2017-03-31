<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\EntityInterface;
use Honeybee\Projection\ProjectionInterface;
use Trellis\Common\Error\RuntimeException;
use Trellis\Runtime\Attribute\AttributeValuePath;

class EmbedPathValidator extends AgaviValidator
{
    protected $resource_type;

    protected function validate()
    {
        $embedded_entity_or_embed_path = $this->getData($this->getArgument());
        $root_entity = $this->fetchRootEntity();
        if (!$root_entity instanceof ProjectionInterface) {
            $this->throwError('missing_resource');
            return false;
        }

        if ($embedded_entity_or_embed_path instanceof EntityInterface) {
            $embedded_entity = $embedded_entity_or_embed_path;
        } elseif (is_string($embedded_entity_or_embed_path)) {
            $embedded_entity = $this->fetchEmbeddedEntity($root_entity, $embedded_entity_or_embed_path);
            if (!$embedded_entity) {
                $this->throwError('unknown_embed');
                return false;
            }
        } else {
            $this->throwError('invalid_type');
            return false;
        }

        $this->export($embedded_entity, $this->getParameter('export', $this->getArgument()));

        return true;
    }

    protected function fetchRootEntity()
    {
        $root_entity_arg = $this->getParameter('resource_arg', 'resource');

        return $this->getData($root_entity_arg);
    }

    protected function fetchEmbeddedEntity(EntityInterface $root_entity, $embed_path)
    {
        try {
            return AttributeValuePath::getAttributeValueByPath($root_entity, $embed_path);
        } catch (RuntimeException $error) {
            return null;
        }
    }

    protected function getProjectionType()
    {
        if ($this->resource_type) {
            return $this->resource_type;
        }

        if (!$this->hasParameter('resource_type')) {
            throw new RuntimeError('Missing required "resource_type" parameter.');
        }

        $this->resource_type = $this->getServiceLocator()->getProjectionTypeMap()->getItem(
            $this->getParameter('resource_type')
        );

        return $this->resource_type;
    }

    protected function getServiceLocator()
    {
        return $this->getContext()->getServiceLocator();
    }
}
