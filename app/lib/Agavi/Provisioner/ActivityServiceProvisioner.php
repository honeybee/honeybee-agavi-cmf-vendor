<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Common\Error\ConfigError;
use Honeybee\EnvironmentInterface;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\Ui\Activity\Activity;
use Honeybee\Ui\Activity\ActivityContainer;
use Honeybee\Ui\Activity\ActivityContainerMap;
use Honeybee\Ui\Activity\ActivityMap;
use Honeybee\Ui\Activity\ActivityServiceInterface;
use Honeybee\Ui\Activity\WorkflowActivityService;
use Honeybee\Ui\Activity\Url;
use Honeybee\Ui\UrlGeneratorInterface;
use Trellis\Runtime\Validator\Rule\Type\UrlRule;

class ActivityServiceProvisioner extends AbstractProvisioner
{
    const ACTIVITY_CONFIG_NAME = 'activities.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $factory_delegate = function (
                EnvironmentInterface $environment,
                WorkflowActivityService $workflow_activity_service,
                AggregateRootTypeMap $aggregate_root_type_map,
                UrlGeneratorInterface $url_generator
            ) use ($service_definition) {
            $activity_container_map = $this->buildActivityContainerMap();
            $service_class = $service_definition->getClass();

            return new $service_class(
                $environment,
                $workflow_activity_service,
                $activity_container_map,
                $aggregate_root_type_map,
                $url_generator
            );
        };

        $service = $service_definition->getClass();

        $this->di_container
            ->delegate($service, $factory_delegate)
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
            $container_data['scope'] = $scope;
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
