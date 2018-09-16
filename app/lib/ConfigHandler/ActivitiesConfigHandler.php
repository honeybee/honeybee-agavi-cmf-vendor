<?php

namespace Honeygavi\ConfigHandler;

use AgaviConfig;
use AgaviToolkit;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Common\Error\ConfigError;
use Honeygavi\Ui\Activity\Url;

class ActivitiesConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/activities/1.0';

    const DEFAULT_VERB = 'read';
    const DEFAULT_URL_TYPE = Url::TYPE_URI;

    protected $allowed_url_types = array(
        Url::TYPE_URI,
        Url::TYPE_URI_TEMPLATE,
        Url::TYPE_ROUTE
    );

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'activities');

        $activity_containers = [];

        foreach ($document->getConfigurationElements() as $configuration) {
            $new_activity_containers = $this->parseActivityContainers($configuration, $document);
            $activity_containers = self::mergeSettings($activity_containers, $new_activity_containers);
        }

        $this->handleContainerInheritance($activity_containers, $document);

        $configuration_code = sprintf('return %s;', var_export($activity_containers, true));

        return $this->generate($configuration_code, $document->documentURI);
    }

    /**
     * Returns the activity containers from the given configuration node.
     *
     * @param AgaviXmlConfigDomElement $configuration configuration node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array of activity containers with their respective activities
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseActivityContainers(
        AgaviXmlConfigDomElement $configuration,
        AgaviXmlConfigDomDocument $document
    ) {
        if (!$configuration->has('activity_containers')) {
            return [];
        }

        $activity_containers_element = $configuration->getChild('activity_containers');

        $activity_containers = [];

        // there may be multiple activity containers, each should have a scope
        foreach ($activity_containers_element->getChildren('activity_container') as $activity_container_element) {
            $scope = $activity_container_element->hasAttribute('scope') ?
                trim($activity_container_element->getAttribute('scope')) :
                '';
            if (empty($scope)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "scope" attribute for a "activity_container" element.',
                        $document->documentURI
                    )
                );
            }

            $extends_scope = $activity_container_element->hasAttribute('extends') ?
                trim($activity_container_element->getAttribute('extends')) :
                '';

            $activity_containers[$scope] = [];
            $activity_containers[$scope]['extends'] = $extends_scope;
            $activity_containers[$scope]['activities'] = $this->parseActivities(
                $activity_container_element,
                $document,
                $scope
            );
        }

        return $activity_containers;
    }

    protected function handleContainerInheritance(&$activity_containers, AgaviXmlConfigDomDocument $document)
    {
        // when an activity_container has an "extends" attribute with a valid scope name, we merge scopes
        foreach ($activity_containers as $scope => &$activity_container) {
            if (!empty($activity_container['extends'])) {
                if (empty($activity_containers[$activity_container['extends']])) {
                    throw new ConfigError(
                        sprintf(
                            'The "extends" attribute value of the activity_container with scope "%s" is invalid. ' .
                            'No activity_container with scope "%s" found in configuration file "%s".',
                            $scope,
                            $activity_container['extends'],
                            $document->documentURI
                        )
                    );
                }
                $activity_container = self::mergeSettings(
                    $activity_containers[$activity_container['extends']],
                    $activity_container
                );
            }
            unset($activity_container['extends']);
        }
    }

    protected function parseActivities(
        AgaviXmlConfigDomElement $container_node,
        AgaviXmlConfigDomDocument $document,
        $scope
    ) {

        $activities = [];

        foreach ($container_node->get('activity') as $activity_node) {
            $name = $activity_node->hasAttribute('name') ? trim($activity_node->getAttribute('name')) : '';
            if (empty($name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for an "activity" element.',
                        $document->documentURI
                    )
                );
            }
            $activities[$name] = $this->parseActivity($activity_node, $document);
            $activities[$name]['scope'] = $scope;
        }

        return $activities;
    }

    protected function parseActivity(AgaviXmlConfigDomElement $activity_node, AgaviXmlConfigDomDocument $document)
    {
        // description for this activity
        $description_node = $activity_node->getChild('description');
        $description = $description_node ? $description_node->getValue() : '';

        // short label for this activity
        $label_node = $activity_node->getChild('label');
        $label = $label_node ? $label_node->getValue() : '';

        // verb (request method equivalent) for this activity
        $verb_node = $activity_node->getChild('verb');
        $verb = $verb_node ? $verb_node->getValue() : AgaviConfig::get('activities.default_verb', self::DEFAULT_VERB);

        // link relations for the target url of this activity
        $rels = [];
        $rels_node = $activity_node->getChild('rels');
        if (!empty($rels_node) && $rels_node->hasChildren('rel')) {
            foreach ($rels_node->get('rel') as $rel_node) {
                $rels[] = $rel_node->getValue();
            }
        }

        // mime types the target url supports as input
        $accepting = [];
        $acception_node = $activity_node->getChild('accepting');
        if (!empty($accepting_node)) {
            foreach ($accepting_node->get('type') as $type_node) {
                $accepting[] = $type_node->getValue();
            }
        }

        // mime types the target url supports as output
        $sending = [];
        $sending_node = $activity_node->getChild('sending');
        if (!empty($sending_node)) {
            foreach ($sending_node->get('type') as $type_node) {
                $sending[] = $type_node->getValue();
            }
        }

        // parse all settings from given settings node
        $settings_node = $activity_node->getChild('settings');
        $settings = $settings_node ? $this->parseSettings($settings_node) : [];
        if (!array_key_exists('form_id', $settings)) {
            $settings['form_id'] = 'randomId' . rand();
        }

        // URL, URI_TEMPLATE or ROUTE_NAME for target url of this activity
        $url = [ 'type' => self::DEFAULT_URL_TYPE, 'value' => '' ];
        $url_node = $activity_node->getChild('url');
        if (!empty($url_node)) {
            $url_value = trim($url_node->getValue());
            $url_type = $url_node->getAttribute('type');
            if (!empty($url_type)) {
                if (!in_array($url_type, $this->allowed_url_types)) {
                    throw new ConfigError(
                        sprintf(
                            'Configuration file "%s" must specify a valid "type" attribute' .
                            'on "url" elements. Valid are: ',
                            $document->documentURI,
                            implode(', ', $this->allowed_url_types)
                        )
                    );
                }
                $url['type'] = $url_type;
            }

            if (empty($url_value)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a valid value for "url" elements.',
                        $document->documentURI
                    )
                );
            }

            if ($url_value === 'null') {
                $url_value = null; // useful for $ro->gen(null, …)
            } else {
                $url_value = AgaviToolkit::literalize(AgaviToolkit::expandDirectives($url_value));
            }

            $url['value'] = $url_value;
        }

        $url['parameters'] = $this->parseUrlParameters($activity_node);

        $url = new Url($url);

        return [
            'name' => trim($activity_node->getAttribute('name')),
            'type' => $activity_node->getAttribute('type', 'general'),
            'description' => $description,
            'label' => $label,
            'verb' => $verb,
            'rels' => $rels,
            'accepting' => $accepting,
            'sending' => $sending,
            'settings' => $settings,
            'url' => $url->toArray()
        ];
    }

    /**
     * Parses the given XML element into an associative nested array.
     *
     * @param AgaviXmlConfigDomElement $parent
     *
     * @return array associative nested array
     */
    protected function parseUrlParameters(AgaviXmlConfigDomElement $parent)
    {
        $params = [];

        if (!$parent->hasChild('url_param') && $parent->hasChild('url_params')) {
            $parent = $parent->getChild('url_params');
        }

        foreach ($parent->getChildren('url_param') as $element) {
            $idx = $element->hasAttribute('name') ? trim($element->getAttribute('name')) : count(array_values($params));

            if ($element->hasChild('url_params')) {
                $params[$idx] = $this->parseUrlParameters($element->getChild('url_params'));
            } elseif (1 < $element->countChildren('url_param')) {
                $params[$idx] = $this->parseUrlParameters($element);
            } else {
                $params[$idx] = AgaviToolkit::literalize(
                    AgaviToolkit::expandDirectives(
                        trim($element->getValue())
                    )
                );
                if ($params[$idx] === 'null') {
                    $params[$idx] = null; // useful for $ro->gen(null, ['foo'=>null]…)
                }
            }
        }

        return $params;
    }
}
