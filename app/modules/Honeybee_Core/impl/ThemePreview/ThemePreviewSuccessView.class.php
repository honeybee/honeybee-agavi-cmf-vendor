<?php

use Honeygavi\Agavi\App\Base\View;
use \AgaviRequestDataHolder;
use Honeybee\Infrastructure\Config\Settings;
use Honeygavi\Ui\Activity\Activity;
use Honeygavi\Ui\Activity\ActivityMap;
use Honeygavi\Ui\Activity\Url;

class Honeybee_Core_ThemePreview_ThemePreviewSuccessView extends View
{
    // 'rels' value accords with the SCSS definition in components/activity and component/dropdown
    protected $sample_activities = [
        'default' => [
            'activity' => [
                'label' => 'Default',
                'description' => 'An activity without a specific type (should fallback to a default one).',
                'rels' => ''

                ,'settings' => [
                    'css' => '',
                ]
            ],
            'link' => 'javascript:void(0);',
            'disabled' => false
        ],
        'navigational' => [
            'activity' => [
                'label' => 'Navigational',
                'description' => 'An activity that takes users somewhere.',
                'rels' => '',
                'settings' => [
                    'css' => 'navigational',
                ]
            ],
            'link' => 'javascript:void(0);',
            'disabled' => false
        ],
        'mutating' => [
            'activity' => [
                'label' => 'Mutating',
                'description' => 'An activity that changes data, e.g. saving a document.',
                'rels' => 'promote edit_resource save_resource create_resource',
                'settings' => [
                    'css' => 'mutating',
                ]
            ],
            'link' => 'javascript:void(0);',
            'disabled' => false
        ],
        'positive' => [
            'activity' => [
                'label' => 'Positive',
                'description' => 'An activity that confirms something.',
                'rels' => '',
                'settings' => [
                    'css' => 'positive',
                ]
            ],
            'link' => 'javascript:void(0);',
            'disabled' => false
        ],
        'warning' => [
            'activity' => [
                'label' => 'Warning',
                'description' => 'An activity that should be used with caution.',
                'rels' => 'demote',
                'settings' => [
                    'css' => 'warning',
                ]
            ],
            'link' => 'javascript:void(0);',
            'disabled' => false
        ],
        'destructive' => [
            'activity' => [
                'label' => 'Destructive',
                'description' => 'An activity that potentially destroys data irreversibly.',
                'rels' => 'delete',
                'settings' => [
                    'css' => 'destructive',
                ]
            ],
            'link' => 'javascript:void(0);',
            'disabled' => false
        ],
    ];

    protected $activity_types = [
        'default' => [
            'selector' => 'default'
        ],
        'navigational' => [
            'selector' => 'navigational'
        ],
        'mutating' => [
            'selector' => 'mutating'
        ],
        'positive' => [
            'selector' => 'positive'
        ],
        'warning' => [
            'selector' => 'warning'
        ],
        'destructive' => [
            'selector' => 'destructive'
        ]
    ];

    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
        $tm = $this->getContext()->getTranslationManager();
        $this->setAttribute(
            'translation_domain',
            AgaviConfig::get('core.theme_preview_translation_domain', $tm->getDefaultDomain())
        );

        $this->setAttribute('_rendererd_sample_navigation', $this->getRenderedSampleNavigation());
        $this->setAttribute('_rendererd_sample_breadcrumbs', $this->getRenderedSampleBreadcrumbs());
        $this->setAttribute('_typed_activity_groups', $this->getRendererdActivityMaps());
        $this->setAttribute('_destructive_activities', $this->getDestructiveActivities());
        $this->setAttribute(
            '_activity_custom_template_activity_group',
            $this->getInnerActivityCustomTemplateActivityMap()
        );
    }

    protected function getRendererdActivityMaps()
    {
        $default_content = '<img src="/static/themes/whitelabel/binaries/icons/favicon-64.png">';

        // Customizations should be set here
        // @todo Add case with custom 'toggle_content'
        $subjects = [
            'Splitbutton-normal' => [
                'settings' => [
                    'as_dropdown' => false,
                    'css' => 'activity-map'
                ]
            ], 'Splitbutton-normal-empty' => [
                'settings' => [
                    'as_dropdown' => false,
                    'toggle_disabled' => true,
                    'css' => 'activity-map'
                ]
            ],
            'Splitbutton-normal-custom_content' => [
                'settings' => [
                    'as_dropdown' => false,
                    'default_content' => $default_content,
                    'css' => 'activity-map'
                ]
            ],
            'Dropdown-normal' => [
                'settings' => [
                    'as_dropdown' => true,
                    'css' => 'activity-map'
                ]
            ], 'Dropdown-normal-empty' => [
                'settings' => [
                    'as_dropdown' => true,
                    'toggle_disabled' => true,
                    'css' => 'activity-map'
                ]
            ],
            'Dropdown-normal-custom_content' => [
                'settings' => [
                    'as_dropdown' => true,
                    'default_content' => $default_content,
                    'css' => 'activity-map'
                ]
            ],
            'Splitbutton-emphasized' => [
                'settings' => [
                    'as_dropdown' => false,
                    'emphasized' => true,
                    'css' => 'activity-map'
                ]
            ], 'Splitbutton-emphasized-empty' => [
                'settings' => [
                    'as_dropdown' => false,
                    'emphasized' => true,
                    'css' => 'activity-map',
                    'toggle_disabled' => true,
                ]
            ],
            'Splitbutton-emphasized-custom_content' => [
                'settings' => [
                    'as_dropdown' => false,
                    'emphasized' => true,
                    'css' => 'activity-map',
                    'default_content' => $default_content,
                ]
            ],
            'Dropdown-emphasized' => [
                'settings' => [
                    'as_dropdown' => true,
                    'emphasized' => true,
                    'css' => 'activity-map'
                ]
            ], 'Dropdown-emphasized-empty' => [
                'settings' => [
                    'as_dropdown' => true,
                    'emphasized' => true,
                    'css' => 'activity-map',
                    'toggle_disabled' => true,
                ]
            ],
            'Dropdown-emphasized-custom_content' => [
                'settings' => [
                    'as_dropdown' => true,
                    'emphasized' => true,
                    'default_content' => $default_content,
                    'css' => 'activity-map'
                ]
            ]
        ];

        foreach ($this->activity_types as $activity_type_name => $activity_type) {
            $activity_type_selector = $activity_type['selector'];

            foreach ($subjects as $subject_name => $subject) {
                // create activity map
                $activity_map = new ActivityMap();

                foreach ($this->sample_activities as $key => $activity_custom_state) {
                    $activity_settings = array_merge_recursive(
                        [ 'form_id' => 'randomId-' . rand() ],
                        $activity_custom_state['activity']['settings']
                    );

                    $activity_state = [
                        'name' => $key,
                        'label' => $activity_custom_state['activity']['label'],
                        'type' => Activity::TYPE_GENERAL,
                        'description' => $activity_custom_state['activity']['description'],
                        'verb' => 'read',
                        'rels' => $activity_custom_state['activity']['rels'],
                        'settings' => new Settings($activity_settings),
                        'url' => new Url(
                            [
                                'type' => Url::TYPE_URI,
                                'value' => $activity_custom_state['link'],
                            ]
                        )
                    ];

                    $activity = new Activity($activity_state);
                    $activity_map->setItem($key, $activity);
                }

                // render map
                $render_settings = [
                    'default_activity_name' => $activity_type_name,
                    'css' => $subject['settings']['css'] . ' ' . $activity_type_selector   // activity type styling for the subject
                ];
                $render_settings = array_replace_recursive($subject['settings'], $render_settings);

                $rendered_maps[$subject_name] = $this->renderSubject(
                    $activity_map,
                    $render_settings
                );
            }
            $rendered_activity_maps[$activity_type_name] = $rendered_maps;
        }

        return $rendered_activity_maps;
    }

    protected function getDestructiveActivities()
    {
        $destructive_activities = array_slice($this->sample_activities, 3);
        $i = 0;
        foreach ($destructive_activities as &$activity_custom_state) {
            $i++;
            $destructive_activity = [
                'activity' => [
                    'label' => sprintf('Destructive %s', $i),
                    'description' => 'An activity that potentially destroys data irreversibly.'
                ],
                'css' => 'activity destructive'
            ];
            $activity_custom_state = array_replace_recursive($activity_custom_state, $destructive_activity);
        }
        return $destructive_activities;
    }

    protected function getInnerActivityCustomTemplateActivityMap()
    {
        // create activity map

        $activity_map = new ActivityMap();
        $activities = array_slice($this->sample_activities, -4); // default_activity_name activity must be included

        foreach ($activities as $key => $activity_custom_state) {
            $activity_settings = [
                'form_id' => 'randomId-' . rand()
            ];

            $activity_state = [
                'name' => $key,
                'label' => $activity_custom_state['activity']['label'],
                'type' => Activity::TYPE_GENERAL,
                'description' => $activity_custom_state['activity']['description'],
                'verb' => 'read',
                'rels' => $activity_custom_state['activity']['rels'],
                'settings' => new Settings($activity_settings),
                'url' => new Url(
                    [
                        'type' => Url::TYPE_URI,
                        'value' => $activity_custom_state['link'],
                    ]
                )
            ];

            $activity = new Activity($activity_state);
            $activity_map->setItem($key, $activity);
        }

        // render map

        // provide custom settings for a specific activity of the map
        $render_settings = [
            'emphasized' => true,
            'default_activity_name' => 'mutating',
            'default_activity_rels' => 'promote',
            'activity.mutating' => [
                'template' => 'html/dummy/activity_custom_template.twig'
            ],
            'activity.warning' => [
                'template' => 'html/dummy/activity_custom_template.twig'
            ]
        ];

        return $this->renderSubject(
            $activity_map,
            $render_settings
        );
    }

    public function getRenderedSampleNavigation()
    {
        $navigation_service = $this->getServiceLocator()->getNavigationService();

        $navigation = $navigation_service->getNavigation('theme_preview');

        return $this->renderSubject($navigation);
    }

    protected function getRenderedSampleBreadcrumbs()
    {
        $breadcrumbs_activities = $this->getSampleBreadcrumbsActivities();

        // render activities
        $rendererd_breadcrumbs = [];
        foreach ($breadcrumbs_activities as $breadcrumbs_activity) {
            $rendererd_breadcrumbs[] = $this->renderSubject($breadcrumbs_activity);
        }

        return $rendererd_breadcrumbs;
    }

    protected function getSampleBreadcrumbsActivities()
    {
        $breadcrumbs_activity_map = $this->getServiceLocator()->getActivityService()->getActivityMap(
            $this->getViewScope() . '.breadcrumbs'
        );

        $breadcrumbs_activities = $breadcrumbs_activity_map->getItems();

        return $breadcrumbs_activities;
    }
}
