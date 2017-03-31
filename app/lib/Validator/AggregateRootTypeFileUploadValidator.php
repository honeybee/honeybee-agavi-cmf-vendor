<?php

namespace Honeygavi\Validator;

use AgaviConfig;
use AgaviImageFileValidator;
use AgaviRequestDataHolder;
use AgaviUploadedFile;
use AgaviValidator;
use AgaviVirtualArrayPath;
use Honeybee\Common\Error\Error;
use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Logging\LogTrait;
use Honeygavi\Request\HoneybeeUploadedFile;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\HandlesFileInterface;
use Trellis\Runtime\Attribute\HandlesFileListInterface;
use Trellis\Runtime\Attribute\Image\Image;
use Trellis\Runtime\Validator\Result\IncidentInterface;
use Trellis\Runtime\Validator\Rule\Type\SanitizedFilenameRule;

class AggregateRootTypeFileUploadValidator extends AgaviValidator
{
    use LogTrait;

    protected $aggregate_root_type;

    protected $filetype_validator_implementor_map = [
        HandlesFileInterface::FILETYPE_FILE => 'AgaviFileValidator',
        HandlesFileInterface::FILETYPE_IMAGE => 'AgaviImageFileValidator',
        HandlesFileInterface::FILETYPE_VIDEO => 'AgaviFileValidator',
    ];

    protected function validate()
    {
        if ($this->hasMultipleArguments()) {
            throw new Error('Only a single argument is supported on this validator.');
        }

        // get all files from request for the current argument base
        $files =& $this->getData($this->getArgument());

        // no files submitted
        if (empty($files) || !is_array($files)) {
            $this->throwError('no_files');
            return false;
        }

        if (count($files) > 1) {
            $this->throwError('multiple_files');
            return false;
        }

        $attribute_path = null;
        $uploaded_file = null;

        foreach ($files as $name => $file) {
            $attribute_path = $name;
            $uploaded_file = $file;
        }

        $art = $this->getAggregateRootType();
        $attribute = null;
        try {
            $attribute = $art->getAttribute($attribute_path);
        } catch (Exception $e) {
            $this->logInfo(
                'Attribute path specified for AggregateRootType',
                $art->getName(),
                'does not exist:',
                $attribute_path
            );
            $this->throwError('invalid_attribute_path');
            return false;
        }

        if (!$attribute instanceof AttributeInterface) {
            $this->logError('Attribute returned from AggregateRootType does not implement AttributeInterface');
            $this->throwError('unknown_attribute_implementation');
            return false;
        }

        //if ($uploaded_file->getError() === UPLOAD_ERR_OK) {}

        $path = new AgaviVirtualArrayPath($attribute_path);
        $success = $this->validateFileForAttribute($uploaded_file, $attribute, $path);
        if (!$success) {
            $this->throwError();
            return false;
        }

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

        // get a stream for the actually uploaded and validated file (probably from /tmp/)
        $uploaded_file_stream = $uploaded_file->getStream($this->getParameter('stream_read_mode', 'rb'));
        if (false === $uploaded_file_stream) {
            throw new RuntimeError('Could not open read stream to uploaded file: ', $uploaded_file->getTmpName());
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

        $uploaded_file = $uploaded_file->createCopyWith([
            HoneybeeUploadedFile::PROPERTY_LOCATION => $file_identifier,
            HoneybeeUploadedFile::PROPERTY_FILENAME => $this->getSanitizedFilename($uploaded_file->getName()),
            HoneybeeUploadedFile::PROPERTY_FILESIZE => $fss->getSize($target_tempfile_uri),
            HoneybeeUploadedFile::PROPERTY_MIMETYPE => $fss->getMimetype($target_tempfile_uri),
            HoneybeeUploadedFile::PROPERTY_EXTENSION => $extension,
            HoneybeeUploadedFile::PROPERTY_WIDTH => $image_width,
            HoneybeeUploadedFile::PROPERTY_HEIGHT => $image_height
        ]);

        // Reset the uploaded file by reference
        $files[$attribute_path] = $uploaded_file;

        $this->setParameter('export_to_source', AgaviRequestDataHolder::SOURCE_PARAMETERS);
        $this->export($attribute, 'attribute');
        // $this->export($uploaded_file, $this->getBase() . '[%1$s]');

        return $success;
    }

    protected function validateFileForAttribute(
        AgaviUploadedFile $uploaded_file,
        AttributeInterface $attribute,
        AgaviVirtualArrayPath $path
    ) {
        // $this->logDebug('Delegating validation for path', $path, 'of entity type', $attribute->getType()->getName());

        $file_validator = $this->createFileValidatorForAttribute($attribute, $path);
        $file_validator->setParentContainer($this->getParentContainer());

        $result = $file_validator->execute($this->validationParameters);
        // $this->logDebug('argument name of filevalidator:', $file_validator->getArgument(), 'result:', $result);
        if ($result === AgaviValidator::NOT_PROCESSED) {
            $this->logDebug('validator for path', $path, 'was not processed');
            return false;
        }

        if ($result > AgaviValidator::SILENT) {
            //$this->throwErrorInParent($index=null, $affected_arg=null, $arg_relative=false, $set_affected=false)
            $this->logDebug('VALIDATION FAILED FOR FILE:', $file_validator->getArgument());
            foreach ($this->getParentContainer()->getErrorMessages() as $error_message) {
                $this->logDebug('error', $error_message);
            }
            //$this->throwError($file_validator->getArgument());
            return false;
        }

        return true;
    }

    protected function createFileValidatorForAttribute(AttributeInterface $attribute, AgaviVirtualArrayPath $path)
    {
        if (!$attribute instanceof HandlesFileListInterface) {
            throw new RuntimeError(
                sprintf('Attribute at %s must implement %s.', $attribute->getPath(), HandlesFileListInterface::CLASS)
            );
        }

        $implementor = $this->getFileValidatorImplementor($attribute);

        $validator = new $implementor();

        // fiddling
        $validator_definition['name'] = sprintf('_invalid_file_%s', $path->__toString());
        $params = $this->getParameters();
        unset($params['class']);
        $params = array_merge($params, $validator_definition);
        $params['source'] = 'files';

        $validator->initialize($this->getContext(), $params, [ $path->__toString() ], $this->errorMessages);

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
                        'validator_[attribute.path]'
                    )
                );
            }

            $implementor = $this->filetype_validator_implementor_map[$filetype];
        }

        return $implementor;
    }

    protected function getSanitizedFilename($insecure_user_provided_filename)
    {
        $options = $this->getParameter('filename_sanitization_rule_options', []);
        $rule = new SanitizedFilenameRule('filename', $options);
        if ($rule->apply($insecure_user_provided_filename)) {
            return $rule->getSanitizedValue();
        }

        return '';
    }

    protected function getAggregateRootType()
    {
        if (!$this->aggregate_root_type) {
            if (!$this->hasParameter('aggregate_root_type')) {
                throw new RuntimeError('Missing required parameter "aggregate_root_type".');
            }

            $aggregate_root_type = $this->getParameter('aggregate_root_type');
            $this->aggregate_root_type = $this->getServiceLocator()
                ->getAggregateRootTypeMap()
                ->getItem($aggregate_root_type);
        }

        return $this->aggregate_root_type;
    }

    protected function getServiceLocator()
    {
        return $this->getContext()->getServiceLocator();
    }

    protected function checkAllArgumentsSet($throw_error = true)
    {
        return true; // always run this validator
    }
}
