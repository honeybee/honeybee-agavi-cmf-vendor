<?php

namespace Honeygavi\Ui\Renderer\Console\Honeygavi\Ui;

use Honeygavi\Ui\Renderer\EntityListRenderer;
use Honeygavi\Ui\ResourceCollection;
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
