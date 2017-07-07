<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Filter;

use Honeybee\Common\Error\RuntimeError;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;

class HtmlTimestampAttributeListFilterRenderer extends HtmlListFilterRenderer
{
    /**
     * List of intervals available for quick selection from the dropdown
     * Must be compatible with comparators defined in ListConfig
     */
    protected $default_choice_options = [
        // value => label
        '-1 hour' => 'last_hour',
        '-1 day' => 'last_day',
        '-1 week' => 'last_week',
        '-1 month' => 'last_month'
    ];

    /**
     * @TODO MOVE TO THE WIDGET
     * Correspondance table of ES date-math expressions and DatePicker expressions
     */
    // protected $date_math_translation_table = [
    //     // ES-date_math-expression => DatePicker-expression
    // ];

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    // protected function validate()
    // {
    //     $check_keys = [ 'attribute_name', 'choice', 'value', 'from', 'to' ];

    //     $picker_values = (array)$this->getPayload('subject');
    //     if (array_diff_key(array_flip($check_keys), $picker_values)) {
    //         throw new RuntimeError(
    //             'Payload must be an array with the following keys: ' .
    //             join(', ', $check_keys) . '. Provided: ' . join(', ', array_keys($picker_values))
    //         );
    //     }
    // }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/list_filter/timestamp_attribute.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        list($params['from_value'], $params['to_value']) = $this->getRangeValues($params['filter_value']);
        $params['choice_options'] = (array)$this->getOption('choice_options', $this->default_choice_options);
        $params['input_name'] = sprintf('filter[%s]', $params['attribute_name']);
        // $params['translations'] = $this->getWidgetTranslations($params);
        $params['widget_options'] = array_merge(
            [
                'translations' => $this->getWidgetTranslations($params),
                'translations_prefix' => $params['attribute_name']
            ],
            $params['widget_options']
        );

        // if (false === strtotime($picker_values['choice'])) {
        //     // empty value, 'custom' value
        //     $params['choice'] = $picker_values['choice'];
        // } else {
        //     $params['choice'] = (new \DateTime($picker_values['choice']))->format($params['choice_date_format']);
        // }
        // $params['from'] = $picker_values['from'];
        // $params['to'] = $picker_values['to'];

        // // keep the filtering previously selected
        // $params['additional_form_data'] = (array)$this->getOption('additional_form_data', []);
        // // remove locally used parameters
        // unset($params['additional_form_data'][sprintf('filter[%s]', $picker_values['attribute_name'])]);
        // unset($params['additional_form_data'][sprintf('%s_choice', $picker_values['attribute_name'])]);

        return $params;
    }

    protected function getRangeValues($value)
    {
        preg_match_all('#(?:range)\((?<value>.+)\)(?:,|$)#U', $value, $matches);
        $from = $to = '';
        if (isset($matches['value'])) {
            // set range criterias
            foreach ($matches['value'] as $value) {
                preg_match_all('#(?<comparator>[!\w]+):(?<comparand>.+?)(?:,|$)#', $value, $value_matches);

                if (isset($value_matches['comparator'])) {
                    foreach ($value_matches['comparator'] as $index => $value) {
                        switch ($value) {
                            case 'gte':
                            case 'gt':
                                $from = $value_matches['comparand'][$index];
                                break;
                            case 'lte':
                            case 'lt':
                                $to = $value_matches['comparand'][$index];
                                break;
                            case 'eq':
                                $from = $value_matches['comparand'][$index];
                                $to = $value_matches['comparand'][$index];
                                break;
                            default:
                                // no support for: !eq, in, !in
                        }
                    }
                }
            }
        } else {
            // empty or single value
            if (!empty($value)) {
                $from = $value;
                $to = $value;
            }
        }

        return [ $from, $to ];
    }


    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/IntervalPicker');
    }

    protected function getWidgetTranslations($params)
    {
        $translation_keys = [
            $params['attribute_name'] . '_picker_custom'
        ];

        $translations = [];
        foreach ($translation_keys as $key) {
            $translations[$key] = $this->_($key);
        }
        return $translations;
    }
}
