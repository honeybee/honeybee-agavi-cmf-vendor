<?php

namespace Honeybee\Tests\Mock;

use AgaviConfig;
use Workflux\Parser\Xml\StateMachineDefinitionParser;
use Honeygavi\Agavi\Provisioner\StateMachineBuilderProvisioner;

class TestStateMachineBuilderProvisioner extends StateMachineBuilderProvisioner
{
    protected function buildStateMachineDefinitions()
    {
        $state_machine_definitions = [];

        $parser = new StateMachineDefinitionParser();

        $xml_files = include AgaviConfig::get('core.testing_dir') . '/config/workflow_configs.php';
        foreach ($xml_files as $file) {
            $state_machine_definitions = array_merge($state_machine_definitions, $parser->parse($file));
        }

        // put the author workflow on the other test types – this doesn't make much sense, but it
        // saves us from duplicating the xml workflow definitions for tests
        $example_workflow = $state_machine_definitions['honeybee_cmf.test_fixtures.author.default_workflow'];
        $state_machine_definitions['honeybee_cmf.test_fixtures.book.default_workflow'] = $example_workflow;
        $state_machine_definitions['honeybee_cmf.test_fixtures.publication.default_workflow'] = $example_workflow;
        $state_machine_definitions['honeybee_cmf.test_fixtures.publisher.default_workflow'] = $example_workflow;

        return $state_machine_definitions;
    }
}
