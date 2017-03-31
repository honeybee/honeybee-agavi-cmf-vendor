<?php

namespace Honeygavi\Ui\Renderer\Haljson\Honeygavi\Ui\Navigation;

use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Activity\ActivityMap;
use Honeygavi\Ui\Renderer\NavigationRenderer;

class HaljsonNavigationRenderer extends NavigationRenderer
{
    protected function doRender()
    {
        $navigation = $this->getPayload('subject');
        $navigation_activity_map = new ActivityMap();
        foreach ($navigation->getNavigationGroups() as $navigation_group) {
            foreach ($navigation_group->getNavigationItems() as $navigation_item) {
                $activity = $navigation_item->getActivity();
                $activity_name = sprintf('%s.%s', $activity->getScope(), $activity->getName());
                $navigation_activity_map->setItem($activity_name, $activity);
            }
        }

        return $this->renderer_service->renderSubject(
            $navigation_activity_map,
            $this->output_format,
            null,
            [],
            new Settings([
                'view_scope' => $this->getOption('view_scope'),
                'curie' => $this->getOption('curie', 'honeybee')
            ])
        );
    }
}
