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

        $link = [
            'name' => $activity->getName(),
            'href' => $this->getLinkFor($activity),
            // 'rel' => implode(' ', $activity->getRels()),
            // 'rel' => 'http://docs.foo.de/rels?scope='.$activity->getScope().'&name='.$activity->getName(),
            'title' => sprintf('%s.label', $activity->getName()),
        ];

        return $link;
    }
}
