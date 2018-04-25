<?php

namespace Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\DataAccess\Finder\Elasticsearch\CriteriaQueryTranslation;
use Honeygavi\Ui\Filter\FilterCriteria;
use \ArrayIterator;
use \Countable;
use \IteratorAggregate;
use \JsonSerializable;

/**
 * Value object representing the multiple values, relative to a list-filter, and the logical operation to match them.
 *
 * Values can be matched using conjunction or disjuntion operator.
 * - Disjunction (OR) is used by default when value is an array (e.g. submit: filter[foo][]=20&filter[foo][]=30)
 * - Conjunction (AND) is used when having a string of delimiter-serparated values (e.g. submit: filter[foo]=20,30)
 */
class ListFilterValue implements FilterValueInterface, Countable, IteratorAggregate, JsonSerializable
{
    const OP_OR = 'OR';
    const OP_AND = 'AND';
    const DELIMITER_AND = ',';
    const EMPTY_FILTER_VALUE = CriteriaQueryTranslation::QUERY_FOR_EMPTY;

    protected $values = [];
    protected $operator;

    public function __construct($value = null)
    {
        if (is_null($value)) {
            return;
        }
        if (is_array($value)) {
            $this->operator = static::OP_OR;
            $this->values = $value;
        } elseif (is_string($value)) {
            $this->operator = static::OP_AND;
            // resolve eventual special filter values (e.g. filter[date]=range(gte:2018-04-18T09:03:06.834Z,))
            if (!preg_match_all(FilterCriteria::CRITERIA_REGEX, $value, $matches, PREG_SET_ORDER)) {
                $this->values = explode(self::DELIMITER_AND, $value);
            } else {
                foreach ($matches as $match) {
                    array_push($this->values, new FilterCriteria($match['criteria'], $match['value']));
                }
            }
        } else {
            throw new RuntimeError('Invalid type provided as list filter value');
        }

        $this->values = array_unique($this->values);
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function count()
    {
        return count($this->values);
    }

    public function isEmpty()
    {
        return count($this->values) === 0;
    }

    public function isMultiple()
    {
        return $this->count() > 1;
    }

    public function first()
    {
        return $this->values[0] ?? null;
    }

    public function last()
    {
        return $this->values[ $this->count()-1 ] ?? null;
    }

    public function jsonSerialize()
    {
        $this->toArray();
    }

    public function toArray()
    {
        return [
            'values' => $this->values,
            'operator' => $this->operator
        ];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->values);
    }
}
