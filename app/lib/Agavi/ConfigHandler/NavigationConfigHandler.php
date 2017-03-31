<?php

namespace Honeygavi\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Common\Error\ConfigError;

class NavigationConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/navigation/1.0';

    const NODE_NAVIGATIONS = 'navigations';

    const NODE_AVAILABLE_ITEMS = 'available_items';

    const NODE_INCLUDE = 'include';

    const NODE_EXCLUDE = 'exclude';

    const NODE_ITEMS = 'items';

    const NODE_ACTIVITIES = 'activities';

    const NODE_ACTIVITY = 'activity';

    const ATTR_NAME = 'name';

    const ATTR_SCOPE = 'scope';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'navigation');
        $parsed_configuration = array('navigations' => [], 'available_items' => []);
        $default_navigation = null;

        // first traversal: plain parsing without interpolation of includes and excludes
        foreach ($document->getConfigurationElements() as $configuration) {
            $new_navigations = [];

            if ($configuration->hasChild(self::NODE_NAVIGATIONS)) {
                $navigations_node = $configuration->getChild(self::NODE_NAVIGATIONS);
                $default_navigation = $navigations_node->getAttribute('default');
                foreach ($navigations_node as $navigation_node) {
                    $next_navigation = $this->parseNavigation($navigation_node);
                    $new_navigations[$next_navigation['name']] = $next_navigation;
                }
            }

            $available_items = [];
            if ($configuration->hasChild(self::NODE_AVAILABLE_ITEMS)) {
                $available_items = $this->parseAvailableItems(
                    $configuration->getChild(self::NODE_AVAILABLE_ITEMS)
                );
            }

            $parsed_configuration['navigations'] = array_replace_recursive(
                $new_navigations,
                $parsed_configuration['navigations']
            );
            $parsed_configuration['available_items'] = array_replace_recursive(
                $available_items,
                $parsed_configuration['available_items']
            );
        }

        // second traversal: expand the include and exclude directives to concrete items
        $processed_config = $parsed_configuration;
        foreach ($parsed_configuration['navigations'] as $name => $parsed_navigation) {
            $processed_config['navigations'][$name] = $this->expandNavigationGroups(
                $parsed_navigation,
                $parsed_configuration['available_items']
            );
        }

        $processed_config['default_navigation'] = $default_navigation;
        $configuration_code = sprintf('return %s;', var_export($processed_config, true));

        return $this->generate($configuration_code, $document->documentURI);
    }

    protected function parseNavigation(AgaviXmlConfigDomElement $navigation_node)
    {
        $navigation = [
            'name' => $navigation_node->getAttribute(self::ATTR_NAME),
            'groups' => []
        ];

        foreach ($navigation_node->get('groups') as $navigation_group_node) {
            $group_name = $navigation_group_node->getAttribute(self::ATTR_NAME);
            $navigation['groups'][$group_name] = $this->parseNavigationGroup($navigation_group_node);
        }

        return $navigation;
    }

    protected function parseNavigationGroup(AgaviXmlConfigDomElement $navigation_group_node)
    {
        $navigation_group = [];
        $group_items = [];
        $activities = [];

        foreach ($navigation_group_node as $group_item_node) {
            $item_type = $group_item_node->nodeName;
            if ($item_type === self::NODE_INCLUDE || $item_type === self::NODE_EXCLUDE) {
                $group_items[] = array('type' => $item_type, 'items_key' => $group_item_node->getValue());
            } elseif ($item_type === self::NODE_ACTIVITY) {
                $activities[] = array(
                    'scope' =>$group_item_node->getAttribute(self::ATTR_SCOPE),
                    'activity' => $group_item_node->getValue()
                );
            }
        }

        $settings = [];
        if ($navigation_group_node->hasChild(self::NODE_SETTINGS)) {
            $settings = $this->parseSettings($navigation_group_node);
        }

        return array('settings' => $settings, 'items' => $group_items, 'activities' => $activities);
    }

    protected function parseAvailableItems(AgaviXmlConfigDomElement $available_items_node)
    {
        $available_items = [];

        foreach ($available_items_node->getChildren(self::NODE_ITEMS) as $items_node) {
            $module_name = $items_node->getAttribute(self::ATTR_NAME);

            if (isset($available_items[$module_name])) {
                throw new ConfigError(
                    sprintf("The same module %s used more than once within available_items", $module_name)
                );
            }

            $available_items[$module_name] = [];
            foreach ($items_node->get(self::NODE_ACTIVITIES) as $activity_node) {
                $available_items[$module_name][] = array(
                    'scope' =>$activity_node->getAttribute(self::ATTR_SCOPE),
                    'activity' => $activity_node->getValue()
                );
            }
        }

        return $available_items;
    }

    protected function expandNavigationGroups(array $navigation_config, array $available_items)
    {
        foreach ($navigation_config['groups'] as $group_name => $group) {
            $expanded_group_items = $this->expandGroupItems($group['items'], $available_items);
            $expanded_group_items = array_merge($expanded_group_items, $group['activities']);

            if (!empty($expanded_group_items)) {
                $navigation_config['groups'][$group_name]['items'] = $expanded_group_items;
            } else {
                // @todo remove empty navigation groups, as they dont make sense?
                // maybe this is too hardcore and we should present the empty group and have it fixed, if not desired
                unset($navigation_config['groups'][$group_name]);
            }
        }

        return $navigation_config;
    }

    protected function expandGroupItems(array $group_items, array $available_items)
    {
        $expanded_group_items = [];

        foreach ($group_items as $group_item) {
            $items_keys = $group_item['items_key'];
            if (!isset($available_items[$items_keys])) {
                throw new ConfigError(
                    sprintf("Unable to find items for items-group key %s.", $items_keys)
                );
            }

            foreach ($available_items[$items_keys] as $available_item) {
                $found_index = array_search($available_item, $expanded_group_items);

                if ($group_item['type'] === self::NODE_INCLUDE) {
                    if ($found_index === false) {
                        $expanded_group_items[] = $available_item;
                    }
                } else {
                    if ($found_index !== true) {
                        array_splice($expanded_group_items, $found_index, 1);
                    }
                }
            }
        }

        return $expanded_group_items;
    }
}
