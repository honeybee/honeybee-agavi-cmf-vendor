<?php

namespace Honeybee\FrameworkBinding\Agavi\Validator;

use Workflux\Builder\XmlStateMachineBuilder;
use Workflux\Parser\Xml\StateMachineDefinitionParser;
use Workflux\Builder\StateMachineBuilder;

/**
 * Validator for console usage that asks for a valid workflow subject.
 */
class WorkflowSubjectValidator extends ConsoleDialogValidator
{
    /**
     * Validate input and provide a state machine subject for the input value
     */
    protected function validate()
    {
        $success = parent::validate();

        if ($success) {
            $input = $this->getData('input');
            $subject = $this->getData($this->original_argument);
            $builder = new XmlStateMachineBuilder(
                [
                    'state_machine_definition' => $input,
                    'name' => $subject
                ]
            );
            $state_machine = $builder->build();
            $this->export($state_machine, $this->original_argument);
        }

        return $success;
    }

    /**
     * Adds only valid workflow names to the available choices.
     */
    protected function setupProperties()
    {
        parent::setupProperties();

        $input = $this->getData('input');

        $parser = new StateMachineDefinitionParser();
        $workflows = $parser->parse($input);

        $this->choices = array_keys($workflows);
    }
}
