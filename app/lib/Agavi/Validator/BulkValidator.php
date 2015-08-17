<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Honeybee\Model\Command\Bulk\BulkStreamError;
use Honeybee\Model\Command\Bulk\BulkStreamIterator;
use AgaviFileValidator;
use AgaviRequestDataHolder;

class BulkValidator extends AgaviFileValidator
{
    protected function validate()
    {
        $post_file = $this->getData($this->getArgument());
        $bulk_iterator = new BulkStreamIterator($post_file->getStream());

        // traverse the stream to see if it aborts with an error.
        foreach ($bulk_iterator as $item_count => $bulk_item) {
        }

        $last_error = $bulk_iterator->current();
        if ($last_error->getType() !== BulkStreamError::EOF) {
            $this->throwError($last_error->getType());
            return false;
        }

        if ($this->hasParameter('max_items') && $item_count >= $this->getParameter('max_items')) {
            $this->throwError('too_many_items');
            return false;
        }

        $this->export(
            $bulk_iterator,
            $this->getParameter('export', 'bulk_operations'),
            AgaviRequestDataHolder::SOURCE_PARAMETERS
        );

        return true;
    }
}
