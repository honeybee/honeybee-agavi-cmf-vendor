<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;
use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Agavi\Logging\LogTrait;

/**
 * Validates that a given file id exists in the final or temporary storage
 * of the configured aggregate root type. Exports the file_uri is file
 * is found.
 */
class AggregateRootTypeFileExistsValidator extends AgaviValidator
{
    use LogTrait;

    protected $aggregate_root_type;

    protected function validate()
    {
        if ($this->hasMultipleArguments()) {
            $this->throwError('multiple_arguments');
            return false;
        }

        $file_id = $this->getData($this->getArgument());

        if ($file_id === null) {
            $this->throwError('no_value');
            return false;
        }

        $fss = $this->getServiceLocator()->getFilesystemService();
        $art = $this->getAggregateRootType();

        $temp_uri = $fss->createTempUri($file_id, $art);
        $uri = $fss->createUri($file_id, $art);

        // check whether the requested file exists in temporary or
        // final storage and export the appropriate URI
        if ($fss->has($uri)) {
            $this->export($uri, 'file_uri');
            return true;
        } elseif ($fss->has($temp_uri)) {
            $this->export($temp_uri, 'file_uri');
            return true;
        }

        // we're logging an error here as file ids should never be guessed and thus only valid links should exist
        $this->logError(
            'Requested file missing from temporary or final storage of module ' . $art->getName() . ':' . $file_id
        );

        return false;
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
}
