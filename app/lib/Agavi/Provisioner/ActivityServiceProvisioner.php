<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Common\Error\ConfigError;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\Ui\Activity\Activity;
use Honeybee\Ui\Activity\ActivityContainer;
use Honeybee\Ui\Activity\ActivityContainerMap;
use Honeybee\Ui\Activity\ActivityMap;
use Honeybee\Ui\Activity\ActivityServiceInterface;
use Honeybee\Ui\Activity\Url;
use Trellis\Runtime\Validator\Rule\Type\UrlRule;

class ActivityServiceProvisioner extends AbstractProvisioner
{
    const ACTIVITY_CONFIG_NAME = 'activities.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $activity_container_map = $this->buildActivityContainerMap();

        $service = $service_definition->getClass();

        $state = [ ':activity_container_map' => $activity_container_map ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(ActivityServiceInterface::CLASS, $service);
    }

    protected function buildActivityContainerMap()
    {
        $activity_container_map = new ActivityContainerMap();
        foreach ($this->loadActivitiesConfig() as $scope => $container_data) {
            $activity_map = new ActivityMap();
            foreach ($container_data['activities'] as $name => $activity) {
                $activity['settings'] = new Settings($activity['settings']);
                $activity['url'] = $this->buildUrl($activity['url']);
                $activity_map->setItem($name, new Activity($activity));
            }
            $container_data['activity_map'] = $activity_map;
            $activity_container_map->setItem($scope, new ActivityContainer($container_data));
        }

        return $activity_container_map;
    }

    protected function buildUrl($url)
    {
        if (is_array($url)) {
            return new Url($url);
        } elseif ($url instanceof Url) {
            return $url;
        } elseif (is_string($url)) {
            $rule = new UrlRule('url-validation');
            if ($rule->apply($url)) {
                return new Url(
                    [ 'value' => $rule->getSanitizedValue() ]
                );
            }
        } else {
            throw new ConfigError('Given URL must be convertible to an instance of: ' . Url::CLASS);
        }
    }

    protected function loadActivitiesConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::ACTIVITY_CONFIG_NAME
        );
    }
}
