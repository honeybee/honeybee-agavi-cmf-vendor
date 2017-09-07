<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\ValueObjects;

use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Ui\Renderer\GenericSubjectRenderer;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;

class HtmlTimestampRangeRenderer extends GenericSubjectRenderer
{
    const DEFAULT_FORMAT = TimestampAttribute::FORMAT_NATIVE;

    /**
     * List of intervals available for quick selection from the dropdown
     * Must be compatible with comparators defined in Honeygavi\Ui\ListConfig
     */
    public static $default_choice_options = [
        // value => label
        'range(gte:now,lte:+1 hour)' => 'next_hour',
        'range(gte:now,lte:+1 day)' => 'next_day',
        'range(gte:now,lte:+1 week)' => 'next_week',
        'range(gte:now,lte:+1 month)' => 'next_month'
    ];

    protected $supported_comparators = [ 'gte', 'gt', 'lte', 'lt' ];

    protected $supported_comparands = [ 'now' ];

    protected $supported_periods = [ 'year', 'y', 'month', 'M', 'week', 'w', 'day', 'd', 'hour', 'h', 'minute', 'm' ];

    protected function validate()
    {
        $comparand_regex = '#(?<operation>[+-]?)\s*(?<amount>\d+?)\s*(?<unit>' + join('|', $this->supported_periods) + '?)#U';
        $range_value = $this->getPayload('subject');
        $default_value = $this->getOption('default_value', 'range(gte:now)');

        // @todo support empty value?
        if (empty($range_value)) {
            $range_value = $default_value;
        }
        $range_limits = $this->getRangeLimits($range_value);

        if (empty($range_limits)) {
            $range_limits = $this->getRangeLimits($default_value);
        }
        $this->date_format = $this->getOption('date_format', self::DEFAULT_FORMAT);
        // validate limits
        foreach ($range_limits as $limit) {
            if (!is_string($limit['comparator']) || !is_string($limit['comparand'])) {
                throw new RuntimeError('Range limits must be string');
            }
            $this->validateComparator($limit['comparator']);
            $this->validateComparand($limit['comparand'], $comparand_regex);
        }
        $this->range_limits = $range_limits;
    }

    protected function validateComparator($value)
    {
        if (!in_array($value, $this->supported_comparators)) {
            throw new RuntimeError('Comparator "' . $value . '" provided for range limit is not supported');
        }
    }

    protected function validateComparand($value, $regex)
    {
        // @todo Do we want a complete validation? Regex should be improved then

        // if (!\DateTimeImmutable::createFromFormat($date_format, $value)) {
        //     if (!in_array($value, $this->supported_comparands) && preg_match_all($regex, $value, $matche) === false) {
        //         throw new RuntimeError('Comparand "' . $value . '" provided for range limit is not supported');
        //     }
        // }
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/date-range.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['range_values'] = $this->range_limits;
        $params['current_value'] = $this->getPayload('subject');
        $params['choice_options'] = (array)$this->getOption('choice_options', static::$default_choice_options);
        $params['control_name'] = $this->getOption('control_name', 'date-range');
        $params['control_id'] = $this->getOption('control_id', 'date-range-'. rand());
        $params['translation_key_prefix'] = (string)$this->getOption('translation_key_prefix', 'date_range_');
        $params['widget_enabled'] = (bool)$this->getOption('widget_enabled', true);
        $params['widget_options'] = (array)$this->getOption('widget_options', []);
        $params['widget_options']['default_custom_value'] = $this->getOption('default_value', 'range(gte:now)');
        $params['css_prefix'] = (string)$this->getOption('css_prefix');

        return $params;
    }

    // @todo Move in a DateRange value object, rather than having a static method
    public static function getRangeLimits($value = null)
    {
        $range_limits = [];
        preg_match_all('#(?:range)\((?<value>.+)\)(?:,|$)#U', $value, $matches);
        if (isset($matches['value'])) {
            // set range criterias
            foreach ($matches['value'] as $value) {
                preg_match_all('#(?<comparator>[!\w]+):(?<comparand>.+?)(?:,|$)#', $value, $value_matches);
                if (isset($value_matches['comparator'])) {
                    foreach ($value_matches['comparator'] as $index => $value) {
                        switch ($value) {
                            case 'gte':
                            case 'gt':
                                $value = 'gte';
                                break;
                            case 'lte':
                            case 'lt':
                                $value = 'lte';
                                break;
                            default:
                                // no support for: eq, !eq, in, !in
                        }
                        $range_limits[] = [
                            'comparator' => $value,
                            'comparand' => $value_matches['comparand'][$index]
                        ];
                    }
                }
            }
        }

        return $range_limits;
    }

    protected function getDefaultTranslationDomain()
    {
        return parent::getDefaultTranslationDomain() . '.ui';
    }
}
