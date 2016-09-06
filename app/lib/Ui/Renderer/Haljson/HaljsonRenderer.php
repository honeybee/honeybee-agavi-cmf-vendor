<?php

namespace Honeybee\Ui\Renderer\Haljson;

use Honeybee\Ui\Renderer\Renderer;

abstract class HaljsonRenderer extends Renderer
{
    const JSON_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
}
