<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviValidator;
use Honeybee\Common\Util\ArrayToolkit;
use Honeybee\FrameworkBinding\Agavi\Logging\LogTrait;
use Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria;
use Honeybee\Infrastructure\DataAccess\Query\CriteriaList;
use Honeybee\Infrastructure\DataAccess\Query\Query;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Honeybee\Model\Event\AggregateRootEventList;
use Honeybee\Projection\ProjectionInterface;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Attribute\HandlesFileInterface;
use Trellis\Runtime\Attribute\HandlesFileListInterface;
use Trellis\Runtime\Attribute\ListAttribute;
use Trellis\Runtime\Entity\EntityInterface;
use Trellis\Runtime\Validator\Result\IncidentInterface;
use Trellis\Runtime\Validator\Rule\Type\NumberRule;

class ResourceValidator extends AgaviValidator
{
    use LogTrait;

    protected $resource_type;

    protected $aggregate_root_type;

    protected function validate()
    {
        $argument = $this->getArgument();
        $resource = $this->getData($argument);
        if ($resource instanceof ProjectionInterface) {
            $this->export($resource, $this->getParameter('export', $argument));
            return true;
        }
        $head_revision = 0;
        if (true === $this->getParameter('create_fresh_resource', false)) {
            $resource = $this->getProjectionType()->createEntity();
        } else {
            $revision = $this->getPayloadRevision();

            if ($revision) {
                $history = $this->loadHistory();
                if (!$history || $history->getLast()->getSeqNumber() < $revision) {
                    $this->logError('Resource history for revision', $revision, 'of resource', $argument, 'does not exist');
                    $this->throwError('non_existant');
                    return false;
                }

                $head_revision = $history->getLast()->getSeqNumber();
                $resource = $this->loadSpecificResourceRevision($history, $revision);
            } else if ($this->getArgument()) {
                if ($resource = $this->loadCurrentResourceProjection()) {
                    $head_revision = $resource->getRevision();
                }
            }
        }

        if (!$resource) {
            $this->logError('Resource does not exist', $argument);
            $this->throwError('non_existant');
            return false;
        } else if ($this->getParameter('allow_default_payload', false)) {
            $resource = $this->addPayloadToResource($resource);
        }

        $this->export($resource, $this->getParameter('export', $argument));
        $this->export($head_revision, 'head_revision');

        return true;
    }

    protected function addPayloadToResource(ProjectionInterface $resource)
    {
        $resource_payload = [];
        foreach ($resource->getType()->getAttributes() as $attribute) {
            $attribute_payload = $this->getData($attribute->getName());
            //$this->logDebug('addPayloadToResource for', $attribute->getName(), 'with incoming payload:', $attribute_payload);
            if ($attribute instanceof EmbeddedEntityListAttribute) {
                if (is_array($attribute_payload)) {
                    $attribute_payload = $this->filterEmptyPayloadComingFromEmbedTemplates($attribute, $attribute_payload);
                } else {
                    $attribute_payload = [];
                }
            }
            if (is_array($attribute_payload) && ($attribute instanceof HandlesFileListInterface || $attribute instanceof HandlesFileInterface)) {
                $attribute_payload = $this->filterEmptyPayloadComingFromFiles($attribute, $attribute_payload);
            }
            if (!empty($attribute_payload)) {
                //$this->logDebug('actually adding payload for', $attribute->getName(), '=>', $attribute_payload);
                $resource_payload[$attribute->getName()] = $attribute_payload;
            }
        }

        return $resource->getType()->createEntity(array_merge($resource->toNative(), $resource_payload));
    }

    public static function filterEmptyPayloadComingFromEmbedTemplates(ListAttribute $attribute, array $payload)
    {
        $filtered_payload = [];
        foreach ($payload as $embedded_entity_data) {
            if (isset($embedded_entity_data['__template'])) {
                $embed_values = [];
                $embed_type = $attribute->getEmbeddedTypeByPrefix($embedded_entity_data['@type']);
                foreach ($embed_type->getAttributes() as $embedded_attribute) {
                    $attr_name = $embedded_attribute->getName();
                    if (isset($embedded_entity_data[$attr_name]) && !empty($embedded_entity_data[$attr_name])) {
                        $embed_values[$attr_name] = $embedded_entity_data[$attr_name];
                    }
                }
                if (!empty($embed_values)) {
                    $filtered_payload[] = $embedded_entity_data;
                }
            } else {
                $filtered_payload[] = $embedded_entity_data;
            }
        }

        return $filtered_payload;
    }

    public static function filterEmptyPayloadComingFromFiles(
        HandlesFileInterface $attribute,
        array $payload
    ) {
        $filtered_payload = [];
        foreach ($payload as $image_data) {
            if (isset($image_data['[{@= index @}]'])) {
                continue;
            }
            $value = ArrayToolkit::filterEmptyValues($image_data);
            if (!empty($value)) {
                $filtered_payload[] = $value;
            }
        }

        return $filtered_payload;
    }

    protected function loadCurrentResourceProjection()
    {
        $filter_attribute = $this->getParameter('filter_attribute', false);
        if ($filter_attribute && $filter_attribute !== 'identifier') {
            $search_result = $this->getQueryService()->find(
                new Query(
                    new CriteriaList,
                    new CriteriaList([
                        new AttributeCriteria($filter_attribute, $this->getData($this->getArgument()))
                    ]),
                    new CriteriaList,
                    0,
                    1
                )
            );
        } else {
            $search_result = $this->getQueryService()->findByIdentifier($this->getData($this->getArgument()));
        }

        return $search_result->hasResults() ? $search_result->getFirstResult() : null;
    }

    protected function getPayloadRevision()
    {
        if (!$this->hasParameter('revision_arg')) {
            return null;
        }

        $revision = $this->getData($this->getParameter('revision_arg'));
        if ($revision) {
            $number_rule = new NumberRule('valid_revision', [ 'min' => 1 ]);
            if ($number_rule->apply($revision)) {
                return $number_rule->getSanitizedValue();
            }
        }

        return null;
    }

    protected function loadSpecificResourceRevision(AggregateRootEventList $history, $revision)
    {
        $aggregate_root = $this->getAggregateRootType()->createEntity();
        $aggregate_root->reconstituteFrom(
            $history->filter(
                function (AggregateRootEventInterface $event) use ($revision) {
                    return $event->getSeqNumber() <= $revision;
                }
            )
        );

        return $this->getProjectionType()->createEntity($aggregate_root->toNative());
    }

    protected function loadHistory()
    {
        $identifier = $this->getData($this->getArgument());

        if ($identifier instanceof EntityInterface) {
            $identifier = $identifier->getIdentifier();
        }
        $query_result = $this->getQueryService()->findEventsByIdentifier($identifier);
        $history = new AggregateRootEventList($query_result->getResults());

        if ($history->isEmpty()) {
            return null;
        }

        return $history;
    }

    protected function getProjectionType()
    {
        if ($this->resource_type) {
            return $this->resource_type;
        }

        if (!$this->hasParameter('resource_type')) {
            throw new RuntimeError('Missing required "resource_type" parameter.');
        }

        $this->resource_type = $this->getServiceLocator()->getProjectionTypeByPrefix($this->getParameter('resource_type'));

        return $this->resource_type;
    }

    protected function getAggregateRootType()
    {
        if (!$this->aggregate_root_type) {
            $resource_type = $this->getProjectionType();
            $this->aggregate_root_type = $this->getServiceLocator()->getAggregateRootTypeByPrefix(
                $resource_type->getPrefix()
            );
        }

        return $this->aggregate_root_type;
    }

    protected function getServiceLocator()
    {
        return $this->getContext()->getServiceLocator();
    }

    protected function checkAllArgumentsSet($throw_error = true)
    {
        if ($this->getParameter('create_fresh_resource', false)) {
            return true;
        }
        return parent::checkAllArgumentsSet($throw_error);
    }

    protected function getQueryService()
    {
        $data_access_service = $this->getServiceLocator()->getDataAccessService();
        $query_service_map = $data_access_service->getQueryServiceMap();

        return $query_service_map->getByProjectionType($this->getProjectionType());
    }
}
