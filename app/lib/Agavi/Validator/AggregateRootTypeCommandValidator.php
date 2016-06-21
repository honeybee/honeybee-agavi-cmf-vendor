<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviArrayPathDefinition;
use AgaviConfig;
use AgaviValidationIncident;
use AgaviValidator;
use AgaviVirtualArrayPath;
use AgaviWebRequestDataHolder;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\ArrayToolkit;
use Honeybee\FrameworkBinding\Agavi\Logging\LogTrait;
use Honeybee\FrameworkBinding\Agavi\Request;
use Honeybee\FrameworkBinding\Agavi\Request\HoneybeeUploadedFile;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommandInterface;
use Honeybee\Model\Command\AggregateRootTypeCommandInterface;
use Honeybee\Model\Command\EmbeddedEntityTypeCommandList;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Honeybee\Model\Event\AggregateRootEventList;
use Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\AddEmbeddedEntityCommand;
use Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\ModifyEmbeddedEntityCommand;
use Honeybee\Model\Task\ModifyAggregateRoot\RemoveEmbeddedEntity\RemoveEmbeddedEntityCommand;
use Honeybee\Model\Task\TaskConflict;
use Honeybee\Projection\ProjectionInterface;
use Trellis\Common\Collection\Map;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Attribute\GeoPoint\GeoPointAttribute;
use Trellis\Runtime\Attribute\HandlesFileInterface;
use Trellis\Runtime\Attribute\HandlesFileListInterface;
use Trellis\Runtime\Attribute\HasComplexValueInterface;
use Trellis\Runtime\Attribute\Image\Image;
use Trellis\Runtime\Attribute\ListAttribute;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Entity\EntityInterface;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\Validator\Result\IncidentInterface;
use Trellis\Runtime\Validator\Rule\Type\IntegerRule;

class AggregateRootTypeCommandValidator extends AgaviValidator
{
    use LogTrait;

    const DEFAULT_FILE_INPUT_FIELD_NAME = 'file';

    protected $aggregate_root_type;

    protected $files;

    protected $filetype_validator_implementor_map = [
        HandlesFileInterface::FILETYPE_FILE => 'AgaviFileValidator',
        HandlesFileInterface::FILETYPE_IMAGE => 'AgaviImageFileValidator',
        HandlesFileInterface::FILETYPE_VIDEO => 'AgaviFileValidator',
    ];

    protected function validate()
    {
        $command = $this->getData($this->getParameter('command_argument', '__command'));
        if ($command instanceof AggregateRootTypeCommandInterface) {
            $this->export($command, $this->getParameter('export', 'command'));
            return true;
        }

        // get all UNVALIDATED! files from the current request
        $this->files =& $this->validationParameters->getAll('files');

        $command_payload = $this->getValidatedAggregateRootCommandPayload(
            $this->getAggregateRootType()->createEntity()
        );

        if (count($this->parentContainer->getValidatorIncidents($this->getParameter('name'))) > 0) {
            foreach ($this->parentContainer->getValidatorIncidents($this->getParameter('name')) as $incident) {
                //$this->logDebug($incident->getFields());
                foreach ($incident->getErrors() as $error) {
                    $this->logDebug(
                        'Validator "' . $this->getParameter('name') . '" error: ',
                        $error->getName(),
                        $error->getMessage()
                    );
                }
            }
            $this->logDebug('About to throw "invalid_payload" error in validator: ' . $this->getParameter('name'));
            $this->throwError('invalid_payload');
            $this->getDependencyManager()->addDependTokens([ 'invalid_payload' ], $this->getBase());
            return false;
        }

        $this->export(
            $this->createAggregateRootTypeCommand($command_payload),
            $this->getParameter('export', 'command')
        );

        return true;
    }

    protected function createAggregateRootTypeCommand(array $command_payload)
    {
        $command_implementor = $this->getCommandImplementor();
        $command = new $command_implementor(
            array_merge(
                $command_payload,
                [ 'aggregate_root_type' => $this->getAggregateRootType()->getPrefix() ]
            )
        );

        if (!$command instanceof AggregateRootTypeCommandInterface) {
            throw new RuntimeError(
                sprintf(
                    'The configured command type must implement %s, but the given command "%s" does not do so.',
                    AggregateRootTypeCommandInterface::CLASS,
                    get_class($command)
                )
            );
        }

        return $command;
    }

    protected function getValidatedAggregateRootCommandPayload(AggregateRootInterface $aggregate_root)
    {
        $prev_data = $aggregate_root->toNative();
        $changed_values = [];
        $embedded_entities_data = [];

        $successful = true;
        foreach ($this->getAllowedAttributeNames() as $attribute_name) {
            $attribute_post_data = $this->getData($attribute_name);
            if ($attribute_post_data === null) {
                continue;
            }

            $attribute = $this->getAggregateRootType()->getAttribute($attribute_name);
            if ($attribute instanceof EmbeddedEntityListAttribute) {
                $embedded_entities_data[$attribute_name] = $attribute_post_data;
                // error_log(__METHOD__ . " - embed: " . $attribute_name . " - " . print_r($attribute_post_data, true));
            } else {
                list($is_valid, $value_holder) = $this->sanitizeAttributePayload(
                    $attribute,
                    $attribute_post_data,
                    $aggregate_root
                );
                if ($is_valid && !$value_holder->sameValueAs($prev_data[$attribute_name])) {
                    $changed_values[$attribute_name] = $value_holder->toNative();
                }
            }
        }

        $embedded_commands = $this->createEmbeddedEntityCommands($aggregate_root, $embedded_entities_data);

        return [
            'values' => $changed_values,
            'embedded_entity_commands' => new EmbeddedEntityTypeCommandList($embedded_commands)
        ];
    }

    protected function createEmbeddedEntityCommands(EntityInterface $entity, array $post_data)
    {
        $embedded_commands = [];
        foreach ($entity->getType()->getAttributes() as $attribute_name => $attribute) {
            if (!$attribute instanceof EmbeddedEntityListAttribute
                || !array_key_exists($attribute_name, $post_data)
            ) {
                continue;
            }
            $entity_map = new Map;
            foreach ($entity->getValue($attribute_name) as $embedded_entity) {
                $entity_map->setItem($embedded_entity->getIdentifier(), $embedded_entity);
            }
            $recognized_entity_ids = [];

            $attribute_post_data = $this->sanitizeListAttributePayload($post_data[$attribute_name], $attribute);

            foreach ($attribute_post_data as $position => $embedded_entity_payload) {
                if (!isset($embedded_entity_payload['@type'])) {
                    $argument = $this->getAttributePayloadPath($attribute, null, $this->getBase());
                    $argument->push($position);
                    $argument->push('@type');
                    $argument_name = $argument->__toString();
                    $this->throwErrorInParent('missing_embed_type', $argument_name);
                    continue;
                }
                if (isset($embedded_entity_payload['identifier'])
                    && $entity_map->hasKey($embedded_entity_payload['identifier'])
                ) {
                    $recognized_entity_ids[] = $embedded_entity_payload['identifier'];
                    $entity_command = $this->createModifyOrRemoveEmbeddedEntityCommand(
                        $entity_map->getItem($embedded_entity_payload['identifier']),
                        $attribute,
                        $embedded_entity_payload,
                        $position,
                        $entity
                    );
                } else {
                    $entity_command = $this->createAddEmbeddedEntityCommand(
                        $entity->getType()->getAttribute($attribute_name),
                        $embedded_entity_payload,
                        $position
                    );
                }
                if ($entity_command) {
                    $embedded_commands[] = $entity_command;
                }
            }

            foreach ($entity_map as $entity_identifier => $embedded_entity) {
                if (!in_array($entity_identifier, $recognized_entity_ids)) {
                    $embedded_commands[] = new RemoveEmbeddedEntityCommand(
                        [
                            'embedded_entity_type' => $embedded_entity->getType()->getPrefix(),
                            'embedded_entity_identifier' => $entity_identifier,
                            'parent_attribute_name' => $attribute_name
                        ]
                    );
                }
            }
        }

        return $embedded_commands;
    }

    protected function filterEmptyEmbedPayload($payload, EmbeddedEntityListAttribute $attribute)
    {
        $filtered = [];
        $special_attributes = [ 'identifier', '@type' ];
        foreach ($payload as $embed_values) {
            $filtered_embed_values = [];
            foreach ($embed_values as $attribute_name => $value) {
                if (in_array($attribute_name, $special_attributes) || empty($value)) {
                    continue;
                } else {
                    $filtered_embed_values[$attribute_name] = $value;
                }
            }
            if (isset($embed_values['identifier']) && empty($embed_values['identifier'])) {
                unset($embed_values['identifier']);
            }

            if ($attribute->getOption('inline_mode', false)) {
                if (!empty($filtered_embed_values)) {
                    $filtered[] = $embed_values;
                }
            } else {
                $filtered[] = $embed_values;
            }
        }

        return $filtered;
    }

    protected function sanitizeListAttributePayload($payload, ListAttribute $attribute)
    {
        if (!is_array($payload)) {
            return [];
        }

        if ($attribute instanceof EmbeddedEntityListAttribute) {
            $payload = $this->filterEmptyEmbedPayload($payload, $attribute);
            // @todo this coupling is snafu! move the filter* method to a utility class or copy it over ...
            $payload = ResourceValidator::filterEmptyPayloadComingFromEmbedTemplates($attribute, $payload);
        } else {
            // this removes empty values of submitted array payload (e.g. TextListAttribute rendered for new entries)
            $payload = ArrayToolkit::filterEmptyValues($payload);
        }

        $attribute_name = $attribute->getName();
        $lower_boundry = count($payload) - 1;
        $upper_boundry = 0;

        $ordered_payload = array_values($payload);
        $duplicated_payload = [];
        foreach ($payload as $position => $embedded_entity_payload) {
            $embed_action = null;
            if (is_array($embedded_entity_payload) && array_key_exists('__action', $embedded_entity_payload)) {
                $embed_action = $embedded_entity_payload['__action'];
            }
            switch ($embed_action) {
                case '__move-up':
                    unset($embedded_entity_payload['__action']);
                    if ($position > $upper_boundry) {
                        array_splice($ordered_payload, $position, 1);
                        array_splice($ordered_payload, $position - 1, 0, [ $embedded_entity_payload ]);
                    }
                    break;
                case '__move-down':
                    unset($embedded_entity_payload['__action']);
                    if ($position < $lower_boundry) {
                        array_splice($ordered_payload, $position, 1);
                        array_splice($ordered_payload, $position + 1, 0, [ $embedded_entity_payload ]);
                    }
                    break;
                case '__duplicate':
                    unset($embedded_entity_payload['__action']);
                    $duped_entity_data = $embedded_entity_payload;
                    if ($attribute instanceof EmbeddedEntityListAttribute && isset($duped_entity_data['identifier'])) {
                        unset($duped_entity_data['identifier']);
                    }
                    $duplicated_payload[] = $duped_entity_data;
                    break;
                case '__delete':
                    if (!$attribute instanceof EmbeddedEntityListAttribute) {
                        // complex listattribute values
                        unset($embedded_entity_payload['__action']);
                        array_splice($ordered_payload, $position, 1);
                    }
                    break;
                default:
                    // nothing to do here ...
            }
        }

        $new_payload = array_merge($ordered_payload, $duplicated_payload);

        return $new_payload;
    }

    protected function createAddEmbeddedEntityCommand(
        EmbeddedEntityListAttribute $embed_attribute,
        array $entity_data,
        $position
    ) {
        $embedded_entity_type = $embed_attribute->getEmbeddedEntityTypeMap()->getItem(
            $entity_data[ModifyEmbeddedEntityCommand::OBJECT_TYPE]
        );

        $temp_entity = $embedded_entity_type->createEntity();
        list($values, $embedded_data) = $this->getValidatedEmbeddedEntityPayload($temp_entity, $entity_data, $position);
        $embedded_commands = $this->createEmbeddedEntityCommands($temp_entity, $embedded_data);
        if (!empty($values) || !empty($embedded_commands)) {
            return new AddEmbeddedEntityCommand(
                [
                    'embedded_entity_type' => $embedded_entity_type->getPrefix(),
                    'parent_attribute_name' => $embed_attribute->getName(),
                    'values' => $values,
                    'position' => $position,
                    'embedded_entity_commands' => $embedded_commands
                ]
            );
        }

        return null;
    }

    protected function createModifyOrRemoveEmbeddedEntityCommand(
        EntityInterface $embedded_entity,
        EmbeddedEntityListAttribute $embed_attribute,
        array $entity_data,
        $position,
        EntityInterface $parent_entity
    ) {
        $embed_action = isset($entity_data['__action']) ? $entity_data['__action'] : null;
        $attribute_name = $embed_attribute->getName();
        list($changed_values, $embedded_data) = $this->getValidatedEmbeddedEntityPayload(
            $embedded_entity,
            $entity_data,
            $position
        );
        $embedded_entity_type = $embed_attribute->getEmbeddedEntityTypeMap()->getItem(
            $entity_data[ModifyEmbeddedEntityCommand::OBJECT_TYPE]
        );
        $embedded_commands = $this->createEmbeddedEntityCommands($embedded_entity, $embedded_data);
        $old_position = $parent_entity->getValue($embed_attribute->getName())->getKey($embedded_entity);
        if ($embed_action === '__delete') {
            return new RemoveEmbeddedEntityCommand(
                [
                    'embedded_entity_type' => $embedded_entity->getType()->getPrefix(),
                    'embedded_entity_identifier' => $embedded_entity->getIdentifier(),
                    'parent_attribute_name' => $attribute_name
                ]
            );
        } elseif (!empty($changed_values) || !empty($embedded_commands) || $old_position !== $position) {
            return new ModifyEmbeddedEntityCommand(
                [
                    'embedded_entity_type' => $embedded_entity_type->getPrefix(),
                    'embedded_entity_identifier' => $embedded_entity->getIdentifier(),
                    'parent_attribute_name' => $attribute_name,
                    'values' => $changed_values,
                    'position' => $position,
                    'embedded_entity_commands' => $embedded_commands
                ]
            );
        }

        return null;
    }

    protected function getValidatedEmbeddedEntityPayload(EntityInterface $entity, array $post_data, $position)
    {
        $prev_data = $entity->toNative();
        $entity_payload = [];
        $embedded_payload = [];

        foreach ($entity->getType()->getAttributes() as $attribute_name => $attribute) {
            $attribute_post_data = isset($post_data[$attribute_name]) ? $post_data[$attribute_name] : null;
            if ($attribute_post_data === null) {
                continue;
            }

            if ($attribute instanceof EmbeddedEntityListAttribute) {
                $embedded_payload[$attribute_name] = $attribute_post_data;
            } else {
                list($is_valid, $value_holder) = $this->sanitizeAttributePayload(
                    $attribute,
                    $attribute_post_data,
                    $entity,
                    $position
                );
                if ($is_valid && !$value_holder->sameValueAs($prev_data[$attribute_name])) {
                    $entity_payload[$attribute_name] = $value_holder->toNative();
                }
            }
        }

        return [ $entity_payload, $embedded_payload ];
    }

    protected function getAllowedAttributeNames()
    {
        $allowed_attributes = [];

        $whitelisted_attributes = $this->getWhitelistedAttributes();
        $blacklisted_attributes = $this->getBlacklistedAttributes();

        $attribute_names = $this->getAggregateRootType()->getAttributes()->getKeys();
        $allowed_attribute_names = array_diff($whitelisted_attributes, $blacklisted_attributes);

        foreach ($attribute_names as $attribute_name) {
            if (in_array($attribute_name, $blacklisted_attributes)) {
                continue;
            }

            if (!empty($attribute_whitelist)
                && in_array($attribute_name, $attribute_whitelist)
                || empty($attribute_whitelist)
            ) {
                $allowed_attributes[] = $attribute_name;
            }
        }

        return array_unique($allowed_attributes);
    }

    protected function getBlacklistedAttributes()
    {
        $default_blacklist = $this->getAggregateRootType()->getDefaultAttributeNames();

        return array_merge($this->getParameter('attribute_blacklist', []), $default_blacklist);
    }

    protected function getWhitelistedAttributes()
    {
        $default_whitelist = [];

        return $this->getParameter('attribute_whitelist', $default_whitelist);
    }

    protected function getMandatoryAttributes()
    {
        return $this->getParameter(
            'mandatory_attributes',
            $this->getAggregateRootType()->getMandatoryAttributes()->getKeys()
        );
    }

    protected function sanitizeAttributePayload(
        AttributeInterface $attribute,
        $payload,
        EntityInterface $parent_entity = null,
        $entity_position = null
    ) {
        $attribute_payload_path = $this->getAttributePayloadPath($attribute, $entity_position, $this->getBase());
        // if attribute payload covers metadata for one or more files => validate those uploaded files first
        if ($attribute instanceof HandlesFileListInterface) {
            $payload = ArrayToolkit::filterEmptyValues($payload);
            $valid_files = $this->validateFilesForAttribute($attribute, $attribute_payload_path, $payload);
            foreach ($valid_files as $key => $file) {
                if (array_key_exists($key, $payload)) {
                    $payload[$key] = array_merge((array)$payload[$key], $file->getHoneybeeProperties());
                } else {
                    $payload[$key] = $file->getHoneybeeProperties();
                }

                // on Image there's width/height properties in addition to the HandlesFileList::DEFAULT_* properties
                if ($attribute->getFiletypeName() === HandlesFileInterface::FILETYPE_IMAGE) {
                    $payload[$key][Image::PROPERTY_WIDTH] = $file->getWidth();
                    $payload[$key][Image::PROPERTY_HEIGHT] = $file->getHeight();
                }

                //$this->logDebug('FILE PAYLOAD AFTER', $payload[$key]);
                //$this->export($file->getHoneybeeProperties(),$attribute_payload_path->pushRetNew($key)->__toString());
            }
        } elseif ($attribute instanceof HandlesFileInterface) {
            throw new RuntimeError('Please implement single file attribute handling in validation');
            // TODO fix this method call as it is no longer valid
            $valid_file = $this->validateFile($entity_type, $attribute_payload_path);
            if ($valid_file !== false &&
                $valid_file instanceof HoneybeeUploadedFile &&
                $valid_file->hasHoneybeeProperties()
            ) {
                $payload = array_merge(ArrayToolkit::filterEmptyValues($payload), $file->getHoneybeeProperties());
                $this->export($file->getHoneybeeProperties(), $attribute_payload_path->__toString());
            }
        }

        if ($attribute instanceof ListAttribute && is_array($payload)) {
            $payload = $this->sanitizeListAttributePayload($payload, $attribute);
        }

        $value_holder = $attribute->createValueHolder();

        // valueholders with ComplexValue values should not be submitted to validation when
        // there are keys with empty values in the post payload as their validation of that
        // will always fail – thus we return the default valueholder here and thus the value
        // should not be changed in the calling method when the empty value is the same
        if ($attribute instanceof HasComplexValueInterface) {
            if ($attribute instanceof GeoPointAttribute && is_array($payload)) {
                // cases: [0,0] or one of lon/lat is 0 – the filterEmptyValues unfortunately removes the array entries
                // and thus one or both values being zero is not possible while still might be valid for the attribute
                $payload = ArrayToolkit::filterEmptyValues($payload, function ($val) {
                    if ($val === '0' || $val === 0) {
                        return true; // 0 and '0' are valid (non-empty) values for lon/lat
                    }
                    return !empty($val);
                });
            } else {
                $payload = ArrayToolkit::filterEmptyValues($payload);
                if (empty($payload)) {
                    return [ true, $value_holder ];
                }
            }
        }

        $result = $value_holder->setValue($payload, $parent_entity);

        if ($result->getSeverity() > IncidentInterface::NOTICE) {
            $success = false;
            foreach ($result->getViolatedRules() as $rule) {
                foreach ($rule->getIncidents() as $name => $incident) {
                    $error_key = $attribute->getPath() . '.' . $name;
                    $incident_params = $incident->getParameters();
                    $argument = $this->getAttributePayloadPath($attribute, $entity_position, $this->getBase());
                    if (isset($incident_params['path_parts'])) {
                        foreach (array_reverse($incident_params['path_parts']) as $incident_path_part) {
                            $argument->push($incident_path_part);
                        }
                    }
                    $argument_name = $argument->__toString();
                    $this->throwErrorInParent($error_key, $argument_name);
                    // $this->throwErrorInParent($error_key, $argument_name.'-nonexistant-global');
                    $this->logDebug(
                        'Invalid attribute value: ' . $argument_name . ' => ' . $error_key,
                        'Payload:',
                        $payload,
                        ', Incident: ',
                        $incident->getParameters()
                    );
                }
            }
        } else {
            return [ true, $value_holder ];
        }

        return [ false, null ];
    }

    protected function getAggregateRootType()
    {
        if (!$this->aggregate_root_type) {
            if (!$this->hasParameter('aggregate_root_type')) {
                throw new RuntimeError('Missing required paramter "aggregate_root_type".');
            }

            $aggregate_root_type = $this->getParameter('aggregate_root_type');
            $this->aggregate_root_type = $this->getServiceLocator()->getAggregateRootTypeByPrefix($aggregate_root_type);
        }

        return $this->aggregate_root_type;
    }

    protected function throwErrorInParent(
        $index = null,
        $affected_argument = null,
        $arguments_relative = false,
        $set_affected = false
    ) {
        if ($this->incident) {
            $this->parentContainer->addIncident($this->incident);
        }

        $this->incident = new AgaviValidationIncident(
            $this,
            self::mapErrorCode($this->getParameter('severity', 'error'))
        );
        $this->throwError($index, $affected_argument, $arguments_relative, $set_affected);
        $this->parentContainer->addIncident($this->incident);
        $this->incident = null;
    }

    /**
     * @return AgaviVirtualArrayPath
     */
    protected function getAttributePayloadPath($attribute, $replacement, AgaviVirtualArrayPath $current_path)
    {
        $payload_path = clone $current_path;

        $attribute_path_parts = explode('.', $attribute->getPath());
        if (count($attribute_path_parts) > 2) {
            // kick EmbeddedEntityType prefix from array
            array_splice($attribute_path_parts, count($attribute_path_parts) - 2, 1, $replacement);
        }
        foreach ($attribute_path_parts as $attribute_path_part) {
            $payload_path->push($attribute_path_part);
        }

        return $payload_path;
    }

    protected function validateFilesForAttribute(
        AttributeInterface $attribute,
        AgaviVirtualArrayPath $path,
        array $payload
    ) {
        //$this->logDebug(
        //    'Validating files for path', $path, 'of', $attribute->getType()->getName(), '=>', $attribute->getName()
        //);
        $files_from_request =& $this->validationParameters->getAll('files');
        if (empty($files_from_request)) {
            //$this->logDebug('Nothing to validate for path', $path, 'as no files at all are in the request');
            return [];
        }

        $valid_files = [];

        $files_on_this_path_level = AgaviArrayPathDefinition::getValue($path->getParts(), $files_from_request, []);
        if (!empty($files_on_this_path_level)) {
            $input_field_name = $this->getParameter('file_input_field_name', self::DEFAULT_FILE_INPUT_FIELD_NAME);
            // check all given files and validate the actually attached/uploaded ones
            foreach ($files_on_this_path_level as $key => $uploaded_file) {
                // TODO this has to change as it's a bit too simplistic as an approach
                if ($uploaded_file[$input_field_name]->getError() === UPLOAD_ERR_NO_FILE) {
                    //$this->logDebug('no file uploaded for', $attribute->getName(), 'key=', $key, 'path=', $path);
                } elseif ($uploaded_file[$input_field_name]->getError() === UPLOAD_ERR_OK) {
                    //$this->logDebug('file given/uploaded for path:', $path, 'key=', $key);
                    $file = $this->validateFileForAttribute(
                        $uploaded_file[$input_field_name],
                        $attribute,
                        $path->pushRetNew($key)->pushRetNew($input_field_name)
                    );
                    if ($file !== false && $file instanceof HoneybeeUploadedFile) {
                        $valid_files[$key] = $file;
                    }
                } else {
                    // other cases to consider? partial uploads? chunked uploads?
                }
            }
        } else {
            // $this->logDebug('No uploaded binaries in the request for path', $path);
            foreach ($payload as $key => $image_payload) {
                // $this->logDebug('payload key', $key, 'image payload', $image_payload);
                $file = $this->createUploadedFile($attribute, $image_payload);
                if ($file instanceof HoneybeeUploadedFile) {
                    $valid_files[$key] = $file;
                }
            }
        }

        // $this->logDebug('For path', $path, ' the valid files are:', $valid_files);

        return $valid_files;
    }

    protected function validateFileForAttribute(
        HoneybeeUploadedFile $uploaded_file,
        AttributeInterface $attribute,
        AgaviVirtualArrayPath $path
    ) {
        //$this->logDebug('Delegating validation for path', $path, 'of entity type', $attribute->getType()->getName());

        $file_validator = $this->createFileValidatorForAttribute($attribute, $path);
        $file_validator->setParentContainer($this->getParentContainer());

        $result = $file_validator->execute($this->validationParameters); // (*)requestdataholder
        //$this->logDebug('argument name of filevalidator:', $file_validator->getArgument(), 'result:', $result);
        if ($result === AgaviValidator::NOT_PROCESSED) {
            $this->logError('Validator for path', $path, 'was not processed');
            return false;
        }

        if ($result > AgaviValidator::SILENT) {
            //$this->throwErrorInParent($index=null, $affected_arg=null, $args_relative=false, $set_affected=false)
            $this->logDebug('VALIDATION FAILED FOR FILE:', $file_validator->getArgument());
            foreach ($this->getParentContainer()->getErrorMessages() as $error_message) {
                $this->logDebug('error', $error_message);
            }
            //$this->throwError($file_validator->getArgument());
            return false;
        }
        //$this->logDebug('SUCCES FOR FILE:', $file_validator->getArgument(), 'attribute-name='.$attribute->getName());

        $fss = $this->getServiceLocator()->getFilesystemService();

        $extension = $fss->guessExtensionForLocalFile(
            $uploaded_file->getTmpName(),
            $this->getParameter('fallback_extension', '')
        );

        // create an unique identifier usable as a relative location for the file (on filesystems; in databases)
        $file_identifier = $fss->generatePath(
            $attribute,
            $this->getParameter('generated_path_prefix', AgaviConfig::get('core.app_prefix', '')),
            $extension
        );

        // create a URI for a AR specific temporary target location with a relative filename
        // e.g. usertempfiles://user/image/random/uuid.jpg
        $target_tempfile_uri = $fss->createTempUri($file_identifier, $this->getAggregateRootType());

        //$this->logDebug(
        //    sprintf(
        //        'Stream copying "%s" to %s specific temporary location: %s',
        //        $uploaded_file->getTmpName(),
        //        $this->getAggregateRootType()->getName(),
        //        $target_tempfile_uri
        //    )
        //);

        // get a stream for the actually uploaded and validated file (probably from /tmp/)
        $uploaded_file_stream = $uploaded_file->getStream($this->getParameter('stream_read_mode', 'rb'));
        if (false === $uploaded_file_stream) {
            throw new RuntimeError('Could not open read stream to uploaded file: ', $uploaded_file->getTmpName());
        }

        // write uploaded file into temporary target location of that aggregate root type for later move into
        // the actual file storage of that aggregate root type (only move the file to the main storage when
        // the actual AggregateRootCommand was successfully dispatched)
        $success = $fss->writeStream($target_tempfile_uri, $uploaded_file_stream);
        if (!$success) {
            throw new RuntimeError(
                'Writing stream from uploaded file ' . $uploaded_file->getTmpName() .
                ' to temp storage ' . $target_tempfile_uri . ' failed.'
            );
        }

        // image attribute => determine image dimensions and add it to the uploaded file's properties
        $image_width = $uploaded_file->getWidth();
        $image_height = $uploaded_file->getHeight();
        if ($attribute instanceof HandlesFileInterface &&
            ($attribute->getFiletypeName() === HandlesFileInterface::FILETYPE_IMAGE)
        ) {
            // as this may silently fail now the resulting image value object will have zero width/height
            $info = @getimagesize($uploaded_file->getTmpName());
            if ($info !== false) {
                $image_width = $info[0];
                $image_height = $info[1];
            }
        }

        $uploaded_file = $uploaded_file->createCopyWith([
            HoneybeeUploadedFile::PROPERTY_LOCATION => $file_identifier,
            HoneybeeUploadedFile::PROPERTY_FILENAME => $uploaded_file->getName(),
            HoneybeeUploadedFile::PROPERTY_FILESIZE => $fss->getSize($target_tempfile_uri),
            HoneybeeUploadedFile::PROPERTY_MIMETYPE => $fss->getMimetype($target_tempfile_uri),
            HoneybeeUploadedFile::PROPERTY_EXTENSION => $extension,
            HoneybeeUploadedFile::PROPERTY_WIDTH => $image_width,
            HoneybeeUploadedFile::PROPERTY_HEIGHT => $image_height
        ]);

        //$this->logDebug('Uploaded temporary file for', $path, 'is:', $uploaded_file->getHoneybeeProperties());

        return $uploaded_file;
    }

    protected function createFileValidatorForAttribute(AttributeInterface $attribute, AgaviVirtualArrayPath $path)
    {
        $implementor = $this->getFileValidatorImplementor($attribute);
        $validator = new $implementor();

        $parts = $path->getParts(); // remove base "edit"
        array_shift($parts);
        $new_path = new AgaviVirtualArrayPath('');
        foreach ($parts as $part) {
            $new_path->push($part);
        }

        $validator_definition = array_merge([], $attribute->getOptions());
        // TODO get "mandatory" option from entity instead as only that know whether it's actually required?
        if ($attribute->hasOption('mandatory')) {
            $validator_definition['required'] = $attribute->getOption('mandatory');
        } else {
            $validator_definition['required'] = false;
        }

        $validator_definition['name'] = sprintf('_invalid_file_%s', $new_path->__toString());
        // TODO should be taken from this validator and forwarded to the type specific validator
        $errors = $this->errorMessages; //array('' => 'Given file is invalid.');
        // TODO subset of params per validator/file type?
        $params = $this->getParameters();
        unset($params['class']);
        $params = array_merge($params, $validator_definition);
        $params['source'] = 'files';

        $validator->initialize($this->getContext(), $params, array($new_path->__toString()), $errors);

        return $validator;
    }

    protected function getFileValidatorImplementor(AttributeInterface $attribute)
    {
        $attribute_path = $attribute->getPath();
        $attribute_path_validator_parameter = 'validator_' . $attribute_path;

        $filetype = $attribute->getFiletypeName();
        $filetype_validator_parameter = $filetype . '_validator';

        $implementor = $this->getParameter($attribute_path_validator_parameter);
        if (empty($implementor)) {
            $implementor = $this->getParameter($filetype_validator_parameter);
        }

        if (empty($implementor)) {
            if (!array_key_exists($filetype, $this->filetype_validator_implementor_map)) {
                throw new RuntimeError(
                    sprintf(
                        'No default validator implementor found for filetype "%s". Use validator parameter ' .
                        '"%s" to specify a class name. A custom validator may be specified for the current ' .
                        'attribute via "%s" parameter. The pattern is "%s". Extending this validator and ' .
                        'adjusting the defaults is another option.',
                        $filetype,
                        $filetype_validator_parameter,
                        $attribute_path_validator_parameter,
                        'validator_[attribute.path]'
                    )
                );
            }

            $implementor = $this->filetype_validator_implementor_map[$filetype];
        }

        return $implementor;
    }

    protected function createUploadedFile(AttributeInterface $attribute, array $image_payload)
    {
        $location_prop = $attribute->getFileLocationPropertyName();
        if (!isset($image_payload[$location_prop])) {
            $this->logDebug(
                'Image payload of attribute',
                $attribute->getRootType()->getPrefix(),
                $attribute->getPath(),
                'has no property',
                $location_prop,
                $image_payload
            );
            return null;
        }
        $location = $image_payload[$location_prop];

        $fss = $this->getServiceLocator()->getFilesystemService();

        $final_uri = $fss->createUri($location, $this->getAggregateRootType());
        if ($fss->has($final_uri)) {
            return null; // file is already handled and in place
        }

        $temp_uri = $fss->createTempUri($location, $this->getAggregateRootType());

        $size = $fss->getSize($temp_uri);
        $mimetype = $fss->getMimetype($temp_uri);
        $extension = $fss->guessExtensionByMimeType($mimetype);

        // convention here is, that the temp filesystem is always a local filesystem (flysystem LocalAdapter variant)
        $local_fs = $fss->getFilesystem($fss->getTempScheme($this->getAggregateRootType()));
        $local_file_path = $local_fs->applyPathPrefix($location);
        // image attribute => determine image dimensions
        $image_width = 0;
        $image_height = 0;
        if ($attribute instanceof HandlesFileInterface &&
            ($attribute->getFiletypeName() === HandlesFileInterface::FILETYPE_IMAGE)
        ) {
            // as this may silently fail now the resulting image value object will have zero width/height
            $info = @getimagesize($local_file_path);
            if ($info !== false) {
                $image_width = $info[0];
                $image_height = $info[1];
            }
        }

        $uploaded_file = new HoneybeeUploadedFile(
            [
                HoneybeeUploadedFile::PROPERTY_LOCATION => $location,
                HoneybeeUploadedFile::PROPERTY_FILENAME => $image_payload[$attribute->getFileNamePropertyName()] ?: '',
                HoneybeeUploadedFile::PROPERTY_MIMETYPE => $mimetype,
                HoneybeeUploadedFile::PROPERTY_FILESIZE => $size,
                HoneybeeUploadedFile::PROPERTY_EXTENSION => $extension,
                HoneybeeUploadedFile::PROPERTY_WIDTH => $image_width,
                HoneybeeUploadedFile::PROPERTY_HEIGHT => $image_height,
                'tmp_name' => $location, // added to work around AgaviUploadedFile exception
                'is_moved' => true,
                'is_uploaded_file' => false,
            ]
        );

        return $uploaded_file;
    }

    protected function getServiceLocator()
    {
        return $this->getContext()->getServiceLocator();
    }

    protected function checkAllArgumentsSet($throw_error = true)
    {
        return true; // always run this validator
    }

    protected function getCommandImplementor()
    {
        $command_implementor = $this->getParameter('command_implementor');
        if (!$command_implementor) {
            throw new RuntimeError('Missing required parameter "command_implementor".');
        }
        if (!class_exists($command_implementor)) {
            throw new RuntimeError(
                sprintf('Unable to load configured command_implementor: "%s".', $command_implementor)
            );
        }

        return $command_implementor;
    }

    protected function throwError(
        $index = null,
        $affectedArgument = null,
        $argumentsRelative = false,
        $setAffected = false
    ) {
        parent::throwError($index, $affectedArgument, $argumentsRelative, $setAffected);

        $this->logDebug(
            'Validation error on aggregate-root-type',
            $this->getParameter('aggregate_root_type'),
            'aggregate-root-identifier is',
            $this->getAggregateRootIdentifierForLogging(),
            'when creating command',
            $this->getParameter('command_implementor'),
            '– error is:',
            $index,
            '– affected argument is:',
            $affectedArgument
        );
    }

    protected function getAggregateRootIdentifierForLogging()
    {
        if ($this->hasParameter('identifier_arg')) {
            return 'not-specified';
        }

        $identifier_arg = $this->getParameter('identifier_arg');
        $identifier = $this->getData($identifier_arg);
        if (!$identifier) {
            return 'missing-from-payload';
        }

        if ($identifier instanceof EntityInterface) {
            $identifier = $identifier->getIdentifier();
        }

        return $identifier;
    }
}
