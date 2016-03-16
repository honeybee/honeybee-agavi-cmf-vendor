<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Infrastructure\Job\Strategy\JobStrategy;
use Honeybee\Infrastructure\Job\Strategy\Retry\NoRetry;

class JobsConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/jobs/1.0';

    const DEFAULT_STRATEGY_IMPLEMENTOR = JobStrategy::CLASS;

    const DEFAULT_RETRY_STRATEGY = NoRetry::CLASS;

    const DEFAULT_FAILURE_STRATEGY = NoRetry::CLASS;

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'jobs');

        $jobs = [];

        // iterate over configuration nodes and merge settings recursively
        foreach ($document->getConfigurationElements() as $configuration) {
            $new_jobs = $this->parseJobs($configuration, $document);
            $jobs = self::mergeSettings($jobs, $new_jobs);
        }

        $config_code = sprintf('return %s;', var_export($jobs, true));

        return $this->generate($config_code, $document->documentURI);
    }

    protected function parseJobs(AgaviXmlConfigDomElement $configuration, AgaviXmlConfigDomDocument $document)
    {
        if (!$configuration->has('jobs')) {
            return [];
        }

        $jobs_element = $configuration->getChild('jobs');

        $jobs = [];

        foreach ($jobs_element->getChildren('job') as $job_element) {
            $class = $job_element->getChild('class')->getValue();
            $name = $job_element->getAttribute('name');

            $settings = [];
            $settings_element = $job_element->getChild('settings');
            if ($settings_element) {
                $settings = $this->parseSettings($settings_element);
            }

            if ($job_element->hasChild('strategy')) {
                $strategy_element = $job_element->getChild('strategy');
            } else {
                $strategy_element = $job_element;
            }
            $strategy = $this->parseStrategy($strategy_element);

            $jobs[$name] = [
                'class' => $class,
                'settings' => $settings,
                'strategy' => $strategy
            ];
        }

        return $jobs;
    }

    protected function parseStrategy(AgaviXmlConfigDomElement $strategy_parent)
    {
        $strategy_implementor = $strategy_parent->hasAttribute('implementor')
            ? $strategy_parent->getAttribute('implementor')
            : self::DEFAULT_STRATEGY_IMPLEMENTOR;

        if ($strategy_parent->hasChild('retry')) {
            $retry_element = $strategy_parent->getChild('retry');
            $retry_strategy['implementor'] = $retry_element->getAttribute('implementor');
            if ($retry_element->hasChild('settings')) {
                $settings_parent = $retry_element->getChild('settings');
            } else {
                $settings_parent = $retry_element;
            }
            $retry_strategy = [
                'implementor' => $retry_element->getAttribute('implementor'),
                'settings' => $this->parseSettings($settings_parent)
            ];
        } else {
            $retry_strategy = [
                'implementor' => self::DEFAULT_RETRY_STRATEGY,
                'settings' => []
            ];
        }

        if ($strategy_parent->hasChild('failure')) {
            $failure_element = $strategy_parent->getChild('failure');
            if ($failure_element->hasChild('settings')) {
                $settings_parent = $failure_element->getChild('settings');
            } else {
                $settings_parent = $failure_element;
            }
            $failure_strategy = [
                'implementor' => $failure_element->getAttribute('implementor'),
                'settings' => $this->parseSettings($settings_parent)
            ];
        } else {
            $failure_strategy = [
                'implementor' => self::DEFAULT_FAILURE_STRATEGY,
                'settings' => []
            ];
        }

        return [
            'implementor' => $strategy_implementor,
            'retry' => $retry_strategy,
            'failure' => $failure_strategy
        ];
    }
}
