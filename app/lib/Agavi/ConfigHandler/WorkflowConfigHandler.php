<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use AgaviXmlConfigHandler;

/**
 * WorkflowConfigHandler parses configuration files that follow the honeybee workflow markup.
 */
class WorkflowConfigHandler extends AgaviXmlConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/workflow/1.0';

    /**
     * Execute this configuration handler.
     *
     * @param      string An absolute filesystem path to a configuration file.
     * @param      string An optional context in which we are currently running.
     *
     * @return     string Data to be written to a cache file.
     *
     * @throws     <b>AgaviUnreadableException</b> If a requested configuration
     *                                             file does not exist or is not
     *                                             readable.
     * @throws     <b>AgaviParseException</b> If a requested configuration file is
     *                                        improperly formatted.
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'workflow');
        $config = $document->documentURI;
        $data = array();

        foreach ($document->getConfigurationElements() as $config_node) {
            $parsed_steps = array();

            foreach ($config_node->get('workflows') as $workflow) {
                foreach ($workflow->getChild('steps')->get('step') as $step_node) {
                    $plugin_node = $step_node->getChild('plugin');
                    $parsed_gates = $this->parseGates($plugin_node->getChild('gates'));
                    $parsed_steps[$step_node->getAttribute('name')] = array(
                        'description' => $step_node->getChild('description')->nodeValue,
                        'plugin' => array(
                            'type' => $plugin_node->getAttribute('type'),
                            'gates' => $parsed_gates,
                            'parameters' => $plugin_node->getAgaviParameters()
                        )
                    );
                }

                $workflow_config = array(
                    'name' => $workflow->getAttribute('name'),
                    'description' => $workflow->getChild('description')->nodeValue,
                    'start_at' => $workflow->getChild('start_at')->nodeValue,
                    'steps' => $parsed_steps
                );
                $this->verifyWorkflowLogic($workflow_config);

                $data[$workflow->getAttribute('name')] = $workflow_config;
            }
        }

        $config_code = sprintf('return %s;', var_export($data, true));

        return $this->generate($config_code, $config);
    }

    /**
     * Grab the gate definitions from the given gates container
     * and return a common structure for representing gate data towards the code using the config (WorkflowHandler).
     *
     * @param AgaviXmlConfigDomElement $gates_node
     * @return array
     */
    protected function parseGates(AgaviXmlConfigDomElement $gates_node)
    {
        $parsed_gates = array();

        foreach (array('step', 'workflow', 'end') as $gate_type) {
            /* @var $gate_node AgaviXmlConfigDomElement */
            foreach ($gates_node->get('gate_' . $gate_type) as $gate_node) {
                $gate_target = trim($gate_node->nodeValue);
                $gate_data = array('type' => $gate_type);
                if ('end' !== $gate_type) {
                    $gate_data['target'] = empty($gate_target) ? null : $gate_target;
                }
                $parsed_gates[$gate_node->getAttribute('name')] = $gate_data;
            }
        }

        return $parsed_gates;
    }

    /**
     * Verify that the given the workflow definition.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    protected function verifyWorkflowLogic(array $workflow_config) // @codingStandardsIgnoreEnd
    {
        // @todo Check if all gates refer to existing targets (steps, workflows, etc)
        // and throw an AgaviParseException if not.
    }
}
