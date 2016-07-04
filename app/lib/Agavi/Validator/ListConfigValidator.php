<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviConfig;
use AgaviNumberValidator;
use AgaviStringValidator;
use AgaviValidator;
use Honeybee\Ui\ListConfigInterface;
use Honeybee\FrameworkBinding\Agavi\Validator\SortSyntaxValidator;

// @todo implement ACL check for user for given "view_scope"? action could forward to NoPermission view...
class ListConfigValidator extends AgaviValidator
{
    const DEFAULT_EXPORT = 'list_config';

    protected static $child_validators = array(
        'as' => array(
            'class' => DisplayModeValidator::CLASS,
            'type' => 'string',
            'values' => array('table', 'grid'),
            'strict' => true,
            'case' => true,
            'required' => true,
            'errors' => array(
                '' => 'Invalid display mode given. Use "table" or "grid".'
            )
        ),
        'offset' => array(
            'class' => AgaviNumberValidator::CLASS,
            'min' => 0,
            'max' => 2147483647,
            'type' => 'integer'
        ),
        'limit' => array(
            'class' => AgaviNumberValidator::CLASS,
            'min' => 1,
            'max' => 2147483647,
            'type' => 'integer'
        ),
        'page' => array(
            'class' => AgaviNumberValidator::CLASS,
            'min' => 1,
            'max' => 2147483647,
            'type' => 'integer'
        ),
        'search' => array(
            'class' => AgaviStringValidator::CLASS,
            'min' => 1
        ),
        'filter' => array(
            'class' => ArrayValidator::CLASS
        ),
        'sort' => [
            'class' => SortSyntaxValidator::CLASS,
            'required' => false,
        ]
    );

    protected function validate()
    {
        $success = true;
        $view_config = null;
        $argument_name = $this->getArgument();
        if ($argument_name) {
            $list_config = $this->getData($argument_name);
            if (!$list_config instanceof ListConfigInterface) {
                $this->throwError('type');
                $success = false;
            }
        } else {
            $success = $this->validateChildArguments();
            if ($success) {
                $validation_data = $this->getChildArgumentsData();

                // set sensible default values where necessary
                if (!array_key_exists('limit', $validation_data)) {
                    $validation_data['limit'] = (int)$this->getParameter(
                        'default_limit',
                        AgaviConfig::get('default.list_items', 25)
                    );
                }

                // if offset is not set, try to use page via: offset=((page-1)*limit)
                if (!array_key_exists('offset', $validation_data) && array_key_exists('page', $validation_data)) {
                    $validation_data['offset'] = (((int)$validation_data['page'])-1) * ((int)$validation_data['limit']);
                    unset($validation_data['page']); // no longer needed as the offset is used internally
                } elseif (array_key_exists('offset', $validation_data)) {
                    unset($validation_data['page']); // no longer needed as the offset is used internally
                }

                $list_config_implementor = $this->getParameter('list_config_implementor', 'Honeybee\\Ui\\ListConfig');
                $list_config = new $list_config_implementor($validation_data);
            }
        }

        if ($success) {
            $this->export(
                $list_config,
                $this->getParameter(
                    'export',
                    $this->getArgument() ?: self::DEFAULT_EXPORT
                )
            );
        }

        return $success;
    }

    protected function validateChildArguments()
    {
        $success = true;
        foreach (self::$child_validators as $argument_name => $validator_definition) {
            $child_validator = $this->createChildValidator($argument_name, $validator_definition);
            $child_validator->setParentContainer($this->getParentContainer());
            if (AgaviValidator::SILENT < $child_validator->execute($this->validationParameters)) {
                $this->throwError($child_validator->getArgument());
                $success = false;
            }
        }

        return $success;
    }

    protected function createChildValidator($argument_name, array $validator_definition)
    {
        $validator = new $validator_definition['class'];
        $validator_definition['required'] = isset($validator_definition['required'])
            ? $validator_definition['required']
            : false;

        $validator_definition['name'] = sprintf(
            '_invalid_list_%s',
            isset($validator_definition['base'])
            ? $validator_definition['base'].'_'.$argument_name
            : $argument_name
        );

        $errors = array('' => 'Invalid value.');
        if (isset($validator_definition['errors']) && is_array($validator_definition['errors'])) {
            $errors = $validator_definition['errors'];
        }

        $validator->initialize(
            $this->getContext(),
            array_merge($this->getParameters(), $validator_definition),
            array($argument_name),
            $errors
        );

        return $validator;
    }

    protected function getChildArgumentsData()
    {
        $list_data = array();

        foreach (self::$child_validators as $argument_name => $validator_definition) {
            $child_argument = isset($validator_definition['base']) ? $validator_definition['base'] : $argument_name;
            if (!isset($list_data[$child_argument])) {
                $value = $this->getData($child_argument);
                if ($value !== null) {
                    $list_data[$child_argument] = $value;
                }
            }
        }

        return $list_data;
    }
}
