<?php

namespace Honeybee\Ui\Renderer\Console\Honeybee\Ui;

use Honeybee\Ui\Renderer\EntityListRenderer;
use Honeybee\Ui\ResourceCollection;
use Honeybee\Common\Error\RuntimeError;

class ConsoleResourceCollectionRenderer extends EntityListRenderer
{
    protected function validate()
    {
        if (!$this->getPayload('subject') instanceof ResourceCollection) {
            throw new RuntimeError(
                sprintf('Payload "subject" must implement "%s".', ResourceCollection::CLASS)
            );
        }
    }

}
