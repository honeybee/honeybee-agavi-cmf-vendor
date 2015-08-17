<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Hierarchy;

use AgaviRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;
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
        //$rendered_resource_collection = $this->renderSubject($resource_collection, [], null, [], 'default.resource_collection', null);
        $rendered_resource_collection = $this->renderSubject(
            $resource_collection,
            [
                'additional_url_parameters' => $default_url_params
            ]
        );
        $this->setAttribute('rendered_resource_collection', $rendered_resource_collection);
        $this->setAttribute('resource_type_name', $resource_type->getName());

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

    protected function setPrimaryActivities(AgaviRequestDataHolder $request_data)
    {

        $activity_service = $this->getServiceLocator()->getActivityService();
        $primary_activities_container = $activity_service->getContainer($this->getViewScope() . '.primary_activities');
        $primary_activities = $primary_activities_container->getActivityMap();

        $rendered_primary_activities = $this->renderSubject(
            $primary_activities,
            [],
            'primary_activities'
        );

        $this->setAttribute('rendered_primary_activities', $rendered_primary_activities);

        return $rendered_primary_activities;
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

        // is one of those activities the current default one?
        $default_activity_map = $sort_activities->filterByUrlParameter('sort', $current_sort_value);
        $default_activity_name = '';
        if (!$default_activity_map->isEmpty()) {
            $default_activity_name = $default_activity_map->getItem($default_activity_map->getKeys()[0])->getName();
        }

        // we generate an id instead of default to a random one, as we need to render the sort
        // activities twice in the html and need unique ids there (by replacing the necessary html snippet)
        $sort_trigger_id = 'sortTrigger' . rand(1, 10000);

        $rendered_sort_activities = $this->renderSubject(
            $sort_activities,
            [
                'default_activity_name' => $default_activity_name,
                'trigger_id' => $sort_trigger_id,
            ],
            'sort_activities'
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
}
