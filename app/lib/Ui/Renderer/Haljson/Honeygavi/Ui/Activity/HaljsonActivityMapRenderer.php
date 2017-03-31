<?php

namespace Honeygavi\Ui\Renderer\Haljson\Honeygavi\Ui\Activity;

use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Renderer\ActivityMapRenderer;

class HaljsonActivityMapRenderer extends ActivityMapRenderer
{
    /**
     * @return array
     */
    protected function doRender()
    {
        $activity_map = $this->getPayload('subject');

        // remove all activities that are excluded via config/settings
        $excluded_activities = (array)$this->getOption('excluded_activities', [ 'edit' ]);
        $hidden_activity_names = (array)$this->getOption('hidden_activity_names', []);
        $hidden_activity_names = array_merge($hidden_activity_names, $excluded_activities);
        $activity_map = $activity_map->filter(
            function ($activity) use ($hidden_activity_names) {
                if (in_array($activity->getName(), $hidden_activity_names)) {
                    return false;
                }
                return true;
            }
        );

        if ($activity_map->isEmpty()) {
            return [];
        }

        $activities = [];

        foreach ($activity_map as $activity) {
            // hal+json is more or less read-only as it doesn't have templates or forms support
            // though, we could start supporting hal+json extensions similar to http://rwcbook.github.io/hal-forms/
            if ($activity->getVerb() !== 'read') {
                continue;
            }

            if (in_array($activity->getName(), $hidden_activity_names)) {
                continue; // don't render activities that should not be displayed
            }

            $additional_payload = [
                'subject' => $activity
            ];

            // workflow activities need an 'resource' or 'module' to generate the url correctly, leaky abstraction \o/
            if ($this->hasPayload('resource')) {
                $additional_payload['resource'] = $this->payload->get('resource');
            } elseif ($this->hasPayload('module')) {
                $additional_payload['module'] = $this->payload->get('module');
            }

            // retrieve config for specific activity
            $specific_activity_options_key = 'activity.' . $activity->getName();
            $default_config = $this->getOption($specific_activity_options_key, new Settings());
            $activity_renderer_config = $this->view_config_service->getRendererConfig(
                $this->getOption('view_scope'),
                $this->output_format,
                $specific_activity_options_key,
                $default_config->toArray()
            );

            $render_settings = new Settings([
                'activity_map_options' => [
                    'view_scope' => $this->getOption('view_scope', 'missing.activity_map.view_scope')
                ]
            ]);

            $link = $this->renderer_service->renderSubject(
                $activity,
                $this->output_format,
                $activity_renderer_config,
                $additional_payload,
                $render_settings
            );

            $curie = $this->getOption('curie', 'honeybee');
            $link_name = sprintf(
                '%s:%s~%s',
                $curie,
                $activity->getScope(),
                isset($link['name']) ? $link['name'] : $activity->getName()
            );

            $activities[$link_name] = $link;
        }

        return $activities;
    }
}
