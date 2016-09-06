<?php

namespace Honeybee\Ui\Renderer\Haljson\Honeybee\Ui\Activity;

use Honeybee\Ui\Renderer\ActivityRenderer;

class HaljsonActivityRenderer extends ActivityRenderer
{
    /**
     * @return array
     */
    protected function doRender()
    {
        $activity = $this->getPayload('subject');

        // todo don't generate url for uri-template activity?
        $link = [
            'name' => $activity->getName(),
            'href' => $this->getLinkFor($activity),
            'rel' => implode(' ', $activity->getRels()),
            'title' => sprintf('%s.label', $activity->getName()),
        ];

        return $link;
    }
}
