<?php

namespace Honeygavi\Ui\Renderer\Text\Honeygavi\Ui\Activity;

use Honeygavi\Ui\Renderer\ActivityRenderer;

class TextActivityRenderer extends ActivityRenderer
{
    public function doRender()
    {
        return $this->getLinkfor(
            $this->getPayload('subject')
        );
    }
}
