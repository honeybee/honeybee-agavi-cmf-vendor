<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use AgaviConfig;
use AgaviValidationIncident;
use AgaviValidator;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\ArrayToolkit;
use Honeybee\EntityTypeInterface;
use Honeybee\FrameworkBinding\Agavi\Request\HoneybeeUploadedFile;
use Honeybee\Model\Aggregate\AggregateRootInterface;
use Honeybee\Model\Command\AggregateRootCommand;
use Honeybee\Model\Command\AggregateRootCommandBuilder;
use Honeybee\Model\Command\AggregateRootTypeCommandInterface;
use Shrink0r\Monatic\Result;
use Shrink0r\Monatic\Success;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Attribute\HandlesFileInterface;
use Trellis\Runtime\Attribute\HandlesFileListInterface;
use Trellis\Runtime\Attribute\Image\Image;
use Trellis\Runtime\Attribute\ListAttribute;

class AggregateRootTypeCommandValidator extends AgaviValidator
{
    const DEFAULT_FILE_INPUT_FIELD_NAME = 'file';

    protected $aggregate_root_type;

    protected $filetype_validator_implementor_map = [
        HandlesFileInterface::FILETYPE_FILE => 'AgaviFileValidator',
        HandlesFileInterface::FILETYPE_IMAGE => 'AgaviImageFileValidator',
        HandlesFileInterface::FILETYPE_VIDEO => 'AgaviFileValidator',
    ];

    protected function validate()
    {
        // pass the dutchie
        $command = $this->getData($this->getParameter('command_argument', '__command'));

        // build the shizzle
        if (!$command instanceof AggregateRootTypeCommandInterface) {
            $aggregate_root = $this->getAggregateRootType()->createEntity();
            $request_payload = (array)$this->getData(null);
            $command_values = $this->getCommandValues($request_payload, $aggregate_root);
            $command = $this->buildCommand($command_values, $aggregate_root);
        }

        // export the commazzle
        if ($command instanceof AggregateRootTypeCommandInterface) {
            $this->export($command, $this->getParameter('export', 'command'));
            return true;
        }

        return false;
    }

    protected function getCommandValues(array $request_payload, AggregateRootInterface $aggregate_root)
    {
        return $this->processRequestPayload(
            $request_payload,
            $aggregate_root->getType(),
            '',
            $this->getAllowedAttributeNames()
        );
    }

    protected function buildCommand(array $command_values, AggregateRootInterface $aggregate_root)
    {
        // consider dropping empty command payloads here
        $result = (new AggregateRootCommandBuilder($aggregate_root->getType(), $this->getCommandImplementor()))
            ->fromEntity($aggregate_root)
            ->withValues($command_values)
            ->build();

        return $this->validateBuildResult($result);
    }

    protected function validateBuildResult(Result $result)
    {
        // throw validator errors if the command could not be built
        if (!$result instanceof Success) {
            foreach ($result->get() as $attribute_value_path => $builder_incidents) {
                $this->throwErrorInParent($attribute_value_path, $builder_incidents[0]);
            }
            $this->throwError('invalid_payload');
            $this->getDependencyManager()->addDependTokens([ 'invalid_payload' ], $this->getBase());
            return false;
        }

        return $result->get();
    }

    protected function checkAllArgumentsSet($throw_error = true)
    {
        return true; // always run this validator
    }

    protected function processRequestPayload(
        array $payload,
        EntityTypeInterface $entity_type,
        $path_prefix = '',
        array $filter_names = []
    ) {
        $filtered = [];
        if (empty($filter_names)) {
            $filtered = $payload;
        }

        foreach ($filter_names as $attribute_name) {
            if (!isset($payload[$attribute_name])) {
                continue;
            }

            $attribute = $entity_type->getAttribute($attribute_name);
            $current_prefix = $path_prefix ? $path_prefix . '.' . $attribute_name : $attribute_name;
            if ($attribute instanceof EmbeddedEntityListAttribute) {
                foreach ($payload[$attribute_name] as $position => $entity_payload) {
                    $embedded_type = $attribute->getEmbeddedEntityTypeMap()->getItem($entity_payload['@type']);
                    $filtered[$attribute_name][$position] = $this->processRequestPayload(
                        $entity_payload,
                        $entity_type,
                        $current_prefix . '.' . $position
                    );
                }
            } elseif ($attribute instanceof HandlesFileListInterface) {
                foreach ($payload[$attribute_name] as $position => $file_payload) {
                    $filtered[$attribute_name][$position] = $this->prepareUploadedFile(
                        $file_payload,
                        $attribute,
                        $current_prefix . '.' . $position
                    );
                }
            } else {
                $filtered[$attribute_name] = $payload[$attribute_name];
            }

            if ($attribute instanceof ListAttribute) {
                $filtered[$attribute_name] = ArrayToolkit::filterEmptyValues($filtered[$attribute_name]);
                $filtered[$attribute_name] = $this->applyListAttributeActions($attribute, $filtered[$attribute_name]);
            }
        }

        return $filtered;
    }

    protected function prepareUploadedFile(array $file_payload, HandlesFileListInterface $attribute, $payload_path)
    {
        // extract any uploaded file from the source files
        $uploaded_files = $this->validationParameters->getAll('files');

        $base = (string)$this->getBase();
        $uploaded_files = $base && isset($uploaded_files[$base]) ? $uploaded_files[$base] : $uploaded_files;
        $path_parts = explode('.', $payload_path);
        $path_parts[] = $this->getParameter('file_input_field_name', self::DEFAULT_FILE_INPUT_FIELD_NAME);

        foreach ($path_parts as $path_part) {
            $uploaded_files = &$uploaded_files[$path_part];
        }
        $uploaded_file = $uploaded_files;

        // skip validation if there is no file available
        if (!$uploaded_file || $uploaded_file->getError() !== UPLOAD_ERR_OK) {
            return $file_payload;
        }

        // validate and merge uploaded file data back into payload
        $validated_file = $this->validateFile(
            $uploaded_file,
            $attribute,
            ArrayToolkit::flattenToArrayPath($path_parts)
        );

        // merge uploaded file properties into payload
        if ($validated_file !== false && $validated_file instanceof HoneybeeUploadedFile) {
            $file_properties = $validated_file->getHoneybeeProperties();
            if ($attribute->getFiletypeName() === HandlesFileInterface::FILETYPE_IMAGE) {
                $file_properties[Image::PROPERTY_WIDTH] = $validated_file->getWidth();
                $file_properties[Image::PROPERTY_HEIGHT] = $validated_file->getHeight();
            }

            $file_payload = array_merge($file_payload, $file_properties);
        }

        return $file_payload;
    }

    protected function applyListAttributeActions(ListAttribute $attribute, array $payload)
    {
        $lower_boundary = count($payload) - 1;
        $upper_boundary = 0;
        $ordered_payload = array_values($payload);
        $duplicated_payload = [];
        foreach ($payload as $position => $embedded_payload) {
            $embed_action = null;
            if (is_array($embedded_payload) && array_key_exists('__action', $embedded_payload)) {
                $embed_action = $embedded_payload['__action'];
            }
            switch ($embed_action) {
                case '__move-up':
                    unset($embedded_payload['__action']);
                    if ($position > $upper_boundary) {
                        array_splice($ordered_payload, $position, 1);
                        array_splice($ordered_payload, $position - 1, 0, [ $embedded_payload ]);
                    }
                    break;
                case '__move-down':
                    unset($embedded_payload['__action']);
                    if ($position < $lower_boundary) {
                        array_splice($ordered_payload, $position, 1);
                        array_splice($ordered_payload, $position + 1, 0, [ $embedded_payload ]);
                    }
                    break;
                case '__duplicate':
                    unset($embedded_payload['__action']);
                    $duped_data = $embedded_payload;
                    if ($attribute instanceof EmbeddedEntityListAttribute && isset($duped_data['identifier'])) {
                        unset($duped_data['identifier']);
                    }
                    $duplicated_payload[] = $duped_data;
                    break;
                case '__delete':
                    if (!$attribute instanceof EmbeddedEntityListAttribute) {
                        // complex listattribute values
                        unset($embedded_payload['__action']);
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

    protected function validateFile(HoneybeeUploadedFile $uploaded_file, AttributeInterface $attribute, $array_path)
    {
        $file_validator = $this->createFileValidator($uploaded_file, $attribute, $array_path);
        $file_validator->setParentContainer($this->getParentContainer());

        // execute validator
        $result = $file_validator->execute($this->validationParameters);
        if ($result === AgaviValidator::NOT_PROCESSED || $result > AgaviValidator::SILENT) {
            return false;
        }

        // move the file to a temporary location
        $fss = $this->getContext()->getServiceLocator()->getFilesystemService();
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
            $attribute->getFiletypeName() === HandlesFileInterface::FILETYPE_IMAGE
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

        return $uploaded_file;
    }

    protected function createFileValidator(HoneybeeUploadedFile $file, AttributeInterface $attribute, $array_path)
    {
        if (!$attribute instanceof HandlesFileListInterface) {
            throw new RuntimeError(
                sprintf('Attribute at %s must implement %s.', $attribute->getPath(), HandlesFileListInterface::CLASS)
            );
        }

        $implementor = $this->getFileValidatorImplementor($attribute);
        $validator = new $implementor;

        // fiddling
        $validator_definition['name'] = sprintf('_invalid_file_%s', $array_path);
        $params = $this->getParameters();
        unset($params['class']);
        $params = array_merge($params, $validator_definition);
        $params['source'] = 'files';

        $validator->initialize($this->getContext(), $params, [ $array_path ], $this->errorMessages);

        return $validator;
    }

    protected function getFileValidatorImplementor(AttributeInterface $attribute)
    {
        $attribute_path = $attribute->getPath();
        $attribute_path_validator_parameter = 'validator_' . str_replace('.', '_', $attribute_path);
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
                        'validator_attribute_path'
                    )
                );
            }
            $implementor = $this->filetype_validator_implementor_map[$filetype];
        }

        return $implementor;
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

    protected function throwErrorInParent($attribute_value_path, array $builder_incidents)
    {
        if ($this->incident) {
            $this->parentContainer->addIncident($this->incident);
        }

        $payload_path = $this->getPayloadPath($attribute_value_path);
        foreach ($builder_incidents['incidents'] as $builder_incident_name => $builder_incident) {
            $this->incident = new AgaviValidationIncident(
                $this,
                self::mapErrorCode($this->getParameter('severity', 'error'))
            );
            $this->throwError(
                $builder_incidents['path'] . '.' . $builder_incident_name,
                $payload_path
            );
            $this->parentContainer->addIncident($this->incident);
            $this->incident = null;
        }
    }

    protected function getPayloadPath($attribute_value_path)
    {
        $path_parts = explode('.', $attribute_value_path);
        if ($base = (string)$this->getBase()) {
            array_unshift($path_parts, $base);
        }
        return ArrayToolkit::flattenToArrayPath($path_parts);
    }

    protected function getAggregateRootType()
    {
        if (!$this->aggregate_root_type) {
            if (!$this->hasParameter('aggregate_root_type')) {
                throw new RuntimeError('Missing required parameter "aggregate_root_type".');
            }

            $aggregate_root_type = $this->getParameter('aggregate_root_type');
            $this->aggregate_root_type = $this->getContext()
                ->getServiceLocator()
                ->getAggregateRootTypeMap()
                ->getItem($aggregate_root_type);
        }

        return $this->aggregate_root_type;
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
}
