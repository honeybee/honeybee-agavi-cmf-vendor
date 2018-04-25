<?php

namespace Honeygavi\Ui\Filter;

use \JsonSerializable;
use Honeybee\Common\Error\RuntimeError;

class FilterCriteria implements JsonSerializable
{
    const CRITERIA_REGEX = '#(?<criteria>\w+)\((?<value>.+)\)(?:,|$)#U';
    const SPATIAL_CRITERIA = 'spatial';
    const RANGE_CRITERIA = 'range';
    const MATCH_CRITERIA = 'match';

    protected $criteria;
    protected $value;

    public function __construct($criteria, $value)
    {
        switch ($criteria) {
            case self::SPATIAL_CRITERIA:
            case self::RANGE_CRITERIA:
            case self::MATCH_CRITERIA:
                $this->criteria = $criteria;
                $this->value = $value;
                break;
            default:
                throw new RuntimeError('Unsupported query criteria: ' . $criteria);
        }
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return [
            'criteria' => $this->criteria,
            'value' => $this->value
        ];
    }

    public function __toString()
    {
        return sprintf('%s(%s)', $this->criteria, $this->value);
    }
}
