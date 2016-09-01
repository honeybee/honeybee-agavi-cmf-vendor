<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Hierarchy;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Ui\Activity\Activity;
use Honeybee\Ui\Activity\Url as ActivityUrl;
use Honeybee\Ui\ValueObjects\Pagination;

class HierarchySuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        if ($this->hasAttribute('command') && $this->getContainer()->getRequestMethod() === 'write') {
            $this->getResponse()->setRedirect(
                $this->routing->gen(null, [], [ 'relative' => false ])
            );
            return;
        }

        $this->setupHtml($request_data);

        $default_url_params = [];
        if ($request_data->hasParameter('sort')) {
            $default_url_params['sort'] = $request_data->getParameter('sort');
        }

        if ($request_data->hasParameter('parent_node')) {
            $parent_node = $request_data->getParameter('parent_node');
            $breadcrumbs = [
                [
                    'text' => 'Top',
                    'link' => $this->routing->gen(
                        'module.hierarchy',
                        array_merge($default_url_params, [ 'module' => $parent_node->getType() ])
                    )
                ]
            ];
            foreach (array_filter(explode('/', $parent_node->getMaterializedPath())) as $ancestor_id) {
                $breadcrumbs[] = [
                    'text' => $ancestor_id,
                    'link' => $this->routing->gen(
                        'module.hierarchy',
                        array_merge(
                            $default_url_params,
                            [ 'resource' => $ancestor_id, 'module' => $parent_node->getType() ]
                        )
                    )
                ];
            }
            $this->setAttribute('breadcrumbs', $breadcrumbs);
            $this->setAttribute('parent_node', $parent_node);
            $this->setAttribute(
                'rendered_parent_node',
                $this->renderSubject($parent_node, [], 'hierarchy_parent')
            );
        } else {
            $this->setAttribute('rendered_parent_node', false);
        }
        $resource_collection = $this->getAttribute('resource_collection');
        $resource_type = $this->getAttribute('resource_type');
        $rendered_resource_collection = $this->renderSubject(
            $resource_collection,
            [
                'additional_url_parameters' => $default_url_params
            ]
        );
        $this->setAttribute('rendered_resource_collection', $rendered_resource_collection);
        $this->setAttribute('resource_type_name', $resource_type->getName());

        $this->setSubheaderActivities($request_data);
        $this->setPrimaryActivities($request_data);
        $this->setSearchForm($request_data);
        $this->setSortActivities($request_data);
        $this->setPagination($request_data);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $resource_collection = $this->getAttribute('resource_collection');
        $resource_type = $this->getAttribute('resource_type');

        $resource_type_name = $resource_type->getName();

        $rendered_resource_collection = $this->renderSubject($resource_collection);
        $this->setAttribute('rendered_resource_collection', $rendered_resource_collection);

        $rendered_sort_activities = $this->setSortActivities($request_data);
        $sorting = 'Available sorting:' . PHP_EOL . PHP_EOL . $rendered_sort_activities;

        $settings = [
            'url_parameters' => []
        ];
        if ($request_data->hasParameter('sort')) {
            $settings['url_parameters']['sort'] = $request_data->getParameter('sort');
        }
        $rendered_pagination = $this->setPagination($request_data, $settings);

        $list_config = $request_data->getParameter('list_config');
        $from = $list_config->getOffset() + 1;
        $to = $from + ($resource_collection->count() - 1);

        $number_of_results = $this->getAttribute('number_of_results', 'xxx');

        $text = <<<EOT

# $resource_type_name list (item $from to $to of $number_of_results)

$sorting

$rendered_pagination

$rendered_resource_collection


EOT;

        $this->cliMessage($text);
    }

    protected function setSearchForm(AgaviRequestDataHolder $request_data)
    {
        $activity_service = $this->getServiceLocator()->getActivityService();
        $search_activity = $activity_service->getActivity($this->getViewScope(), 'search');
        $rendered_search_form = $this->renderSubject(
            $search_activity,
            [
                'search_value' => $request_data->getParameter('search'),
                'form_parameters' => [
                    'sort' => $request_data->getParameter('sort')
                ]
            ]
        );
        $this->setAttribute('rendered_search_form', $rendered_search_form);
        $this->setAttribute('search_value', $request_data->getParameter('search'));
    }

    protected function setSortActivities(AgaviRequestDataHolder $request_data)
    {
        $activity_service = $this->getServiceLocator()->getActivityService();

        // get sort activities defined for current view config scope
        $sort_activities_container = $activity_service->getContainer($this->getViewScope() . '.sort_activities');
        $sort_activities = $sort_activities_container->getActivityMap();
        $current_sort_value = $request_data->getParameter('sort');

        $output_format = $this->getOutputFormat();
        $view_scope = $this->getViewScope();

        // we generate an id instead of default to a random one, as we need to render the sort
        // activities twice in the html and need unique ids there (by replacing the necessary html snippet)
        $sort_trigger_id = 'sortTrigger' . rand(1, 10000);

        $default_data = [
            'view_scope' => $view_scope,
        ];

        // get sort_activities renderer config
        $view_config_service = $this->getServiceLocator()->getViewConfigService();
        $renderer_config = $view_config_service->getRendererConfig(
            $view_scope,
            $output_format,
            'sort_activities',
            $default_data
        );

        /** which activity is the current default one?
         *
         * fallbacks order:
         *  - 'sort' url parameter
         *  - eventual renderer config setting
         *  - eventual custom activity name (from setting or validation)
         *  - first activity of the map
         */
        $default_activity_map = $sort_activities->filterByUrlParameter('sort', $current_sort_value);
        $default_activity_name = '';
        if (!$default_activity_map->isEmpty()) {
            $default_activity_name = $default_activity_map->getItem($default_activity_map->getKeys()[0])->getName();
        } else {
            // when a default_activity_name setting is present we ignore the custom 'sort' url parameter
            if ($renderer_config->has('default_activity_name')) {
                $default_activity_name = $renderer_config->get('default_activity_name');
            } elseif (empty($current_sort_value)) {
                if (!$sort_activities->isEmpty()) {
                    $default_activity_name = $sort_activities->getItem($sort_activities->getKeys()[0])->getName();
                }
            } else {
                // set the custom parameter value (when validation allows it)
                $default_activity_name = $current_sort_value;
            }
        }

        // sort_activities renderer settings
        $render_settings = [
            'trigger_id' => $sort_trigger_id,
        ];
        if (!$sort_activities->isEmpty() && !$sort_activities->hasKey($default_activity_name)) {
            // force a dropdown to display the custom value but only allow the choice of configured activities
            $render_settings['as_dropdown'] = 'true';

            $custom_activity = new Activity([
                'name' => $default_activity_name,
                'label' => $default_activity_name.'.label',
                'url' => ActivityUrl::createUri('null'),
                'settings' => new Settings
            ]);
            $sort_activities->setItem($default_activity_name, $custom_activity);
        }

        if (!empty($default_activity_name)) {
            $render_settings['default_activity_name'] = $default_activity_name;
        }

        $renderer_service = $this->getServiceLocator()->getRendererService();
        $rendered_sort_activities = $renderer_service->renderSubject(
            $sort_activities,
            $output_format,
            $renderer_config,
            [],
            new Settings($render_settings)
        );

        $this->setAttribute('sort_trigger_id', $sort_trigger_id);
        $this->setAttribute('rendered_sort_activities', $rendered_sort_activities);

        return $rendered_sort_activities;
    }

    protected function setPagination(AgaviRequestDataHolder $request_data, array $settings = [])
    {
        $number_of_results = $this->getAttribute('number_of_results', 0);
        $list_config = $request_data->getParameter('list_config');
        $offset = $list_config->getOffset();
        $limit_per_page = $list_config->getLimit();

        $pagination = Pagination::createByOffset($number_of_results, $limit_per_page, $offset);
        $this->setAttributes($pagination->toArray());

        $rendered_pagination = $this->renderSubject($pagination, $settings);

        $this->setAttribute('rendered_pagination', $rendered_pagination);

        return $rendered_pagination;
    }

    public function getBreadcrumbsActivities()
    {
        $breadcrumbs_root_activities = $this->getBreadcrumbsRootActivities();

        return $breadcrumbs_root_activities;
    }

    public function getBreadcrumbsRootActivities()
    {
        return [
            $this->getServiceLocator()->getActivityService()->getActivity(
                $this->getAttribute('resource_type')->getPrefix(),
                'collection'
            )
        ];
    }
}
