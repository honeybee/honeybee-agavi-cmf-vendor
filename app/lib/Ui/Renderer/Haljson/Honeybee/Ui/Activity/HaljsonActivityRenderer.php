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

        $activity_name = $activity->getName();

        $activity_label = '';
        if (empty($activity->getLabel())) {
            $activity_label = sprintf('%s.label', $activity_name);
        }

        // todo don't generate url for uri-template activity?
        $link = [
            'name' => $activity_name,
            'href' => $this->getLinkFor($activity),
            'rel' => implode(' ', $activity->getRels()),
            // 'title' => sprintf('%s.label', $activity->getName()),
        ];

        $label_translation = $this->_($activity_label);
        if ($label_translation !== $activity_label) {
            $link['title'] = $label_translation;
        }

        return $link;
    }
}
