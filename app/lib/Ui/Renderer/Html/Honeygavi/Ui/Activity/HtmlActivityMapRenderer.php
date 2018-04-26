<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Activity;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\ArrayToolkit;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Activity\ActivityInterface;
use Honeygavi\Ui\Activity\ActivityMap;
use Honeygavi\Ui\Renderer\ActivityMapRenderer;

class HtmlActivityMapRenderer extends ActivityMapRenderer
{
    protected static $propagated_options = [
        'as_dropdown',
        'as_list',
        'default_activity_name',
        'emphasized',
        'toggle_disabled',
        'view_scope'
    ];

    protected function validate()
    {
        parent::validate();
        if ($this->hasOption('dropdown_label') && !$this->getOption('as_dropdown', false)) {
            throw new RuntimeError('Option "dropdown_label" is only valid when option "as_dropdown" is true.');
        }
    }

    protected function getDefaultTemplateIdentifier()
    {
        if ($this->getOption('as_dropdown', false)) {
            return $this->output_format->getName() . '/activity_map/as_dropdown.twig';
        } elseif ($this->getOption('as_list', false)) {
            return $this->output_format->getName() . '/activity_map/as_list.twig';
        }
        return $this->output_format->getName() . '/activity_map/as_splitbutton.twig';
    }

    protected function getTemplateParameters()
    {
        $hidden_activities = (array)$this->getOption('hidden_activity_names', []);
        $activity_map = $this->getPayload('subject')->filter(function ($activity) use ($hidden_activities) {
            return !in_array($activity->getName(), $hidden_activities);
        });
        if ($activity_map->isEmpty()) {
            return parent::getTemplateParameters();
        }
        $default_activity = $this->pickDefaultActivity($activity_map);
        $rendered_activities = $this->renderActivities($activity_map);
        ArrayToolkit::moveToTop($rendered_activities, $default_activity->getName());

        return array_merge(
            parent::getTemplateParameters(),
            $this->mapOptionsToParams(),
            $this->getDefaultParameters($rendered_activities, $default_activity)
        );
    }

    protected function pickDefaultActivity(ActivityMap $activity_map)
    {
        $default_activity_name = $this->getOption('default_activity_name', '');
        if (!$activity_map->hasKey($default_activity_name)) {
            $default_activity_name = $activity_map->getKeys()[0];
        }
        return $activity_map->getItem($default_activity_name);
    }

    protected function renderActivities(ActivityMap $activity_map)
    {
        $rendered_activities = [];
        foreach ($activity_map as $activity) {
            $rendered_activities[$activity->getName()] = $this->renderActivity(
                $activity,
                $this->getCommonActivityPayload($activity),
                $this->getRendererConfigFor($activity)
            );
        }
        return $rendered_activities;
    }

    protected function getRendererConfigFor(ActivityInterface $activity)
    {
        $specific_activity_options_key = 'activity.'.$activity->getName();
        return $this->view_config_service->getRendererConfig(
            $this->getOption('view_scope'),
            $this->output_format,
            $specific_activity_options_key,
            $this->getOption($specific_activity_options_key, new Settings)->toArray()
        );
    }

    protected function renderActivity(ActivityInterface $activity, array $payload, ConfigInterface $renderer_config)
    {
        $propagated_options = array_intersect_key($this->getOptions(), array_flip(self::$propagated_options));
        return $this->renderer_service->renderSubject(
            $activity,
            $this->output_format,
            $renderer_config,
            $payload,
            new Settings([ 'activity_map_options' => $propagated_options ])
        );
    }

    protected function mapOptionsToParams()
    {
        return [
            'css' => $this->getOption('css', 'activity-map'),
            'default_css' => $this->getOption('default_css'),
            'default_html_attributes' => $this->getOption('default_html_attributes'),
            'emphasized' => $this->getOption('emphasized', false),
            'html_attributes' => $this->getOption('html_attributes'),
            'more_css' => $this->getOption('more_css'),
            'more_html_attributes' => $this->getOption('more_html_attributes'),
            'name' => $this->getOption('name'),
            'tag' => $this->getOption('tag'),
            'toggle_content' => $this->getOption('toggle_content'),
            'toggle_css' => $this->getOption('css'),
            'toggle_html_attributes' => $this->getOption('toggle_html_attributes'),
            'trigger_css' => $this->getOption('trigger_css'),
            'trigger_html_attributes' => $this->getOption('trigger_html_attributes'),
            'trigger_id' => $this->getOption('trigger_id')
        ];
    }

    protected function getDefaultParameters(array $rendered_activities, ActivityInterface $default_activity)
    {
        $default_rels = [];
        $default_name = $default_activity->getName();
        $default_label = $this->getOption('dropdown_label', $default_activity->getLabel() ?: "$default_name.label");
        if (!$this->getOption('as_dropdown', false)) {
            $default_label = $rendered_activities[$default_name];
            // @todo Should default-activity rels be used just when a replacement default content/label is not provided?
            $default_rels = $this->getOption('default_activity_rels', $default_activity->getRels());
        }
        // the default activity is used as label (and thus removed from the more-activities):
        // - when rendering "as_splitbutton" (note: label is an activity)
        // - when rendering "as_dropdown" and there is no valid label  (note: label is a string)
        if (empty($this->getOption('dropdown_label')) && !$this->getOption('as_list')) {
            unset($rendered_activities[$default_name]);
        }
        $params = [
            'more_activities' => $this->getOption('more_activities', $rendered_activities),
            'toggle_disabled' => $this->getOption('toggle_disabled', false),
            'default_content' => $this->getOption('default_content', $this->_($default_label)),
            'default_description'   => $this->_($this->getOption('default_description', $this->getOption('name', 'activity_map') . '.description')),
            'default_activity_rels' => $default_rels
        ];
        if (!count($params['more_activities'])) {
            $params['toggle_disabled'] = true;
        }
        return $params;
    }

    protected function getCommonActivityPayload(ActivityInterface $activity)
    {
        // workflow activities need an 'resource' or 'module' to generate the url correctly, leaky abstraction \o/
        $payload = [ 'subject' => $activity ];
        if ($this->hasPayload('resource')) {
            $payload['resource'] = $this->payload->get('resource');
        } elseif ($this->hasPayload('module')) {
            $payload['module'] = $this->payload->get('module');
        }
        return $payload;
    }
}
