<?php

namespace Honeygavi\Validator;

use AgaviValidationArgument;
use AgaviValidator;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Validator for console usage that asks for a value when none was specified
 * on the command line interface. The actual validation is delegated to a
 * configurable validator that gets the same parameters as this class.
 * It's possible to display users with a list of choices or ask for valid
 * values for a configurable number of times.
 *
 * Additional parameters to configure the user interaction:
 *
 * - dialog[validator]: fully qualified class name of delegate validator to use for input value validation
 * - dialog[introduction]: informational text that is only displayed once (when the provided cli value wasn't valid)
 * - dialog[question]: question to ask users for input
 * - dialog[default]: default value to use when users just confirm the question with the return key
 * - dialog[attempts]: maximum number of times users are asked for a valid value
 * - dialog[choices]: nested parameters with values for autocompletion or the select dialog
 * - dialog[ignore_choices]: nested parameters with values for ignoring choices in the select dialog
 * - dialog[select]; display set of choices and users can pick one from the list
 * - dialog[confirm]: display question and ask for a boolean value via y/n.
 *
 * @example
 * <validator class="Honeygavi\Validator\ConsoleDialogValidator" name="skeleton" required="false">
        <argument>skeleton</argument>
        <error>You must specify a valid skeleton name (that is, a folder name from dev/templates/).</error>
        <ae:parameter name="pattern">/^[A-Za-z_]+$/</ae:parameter>
        <ae:parameter name="match">true</ae:parameter>
        <ae:parameter name="dialog">
            <ae:parameter name="validator">AgaviRegexValidator</ae:parameter>
            <ae:parameter name="question">Please input the skeleton you want</ae:parameter>
            <ae:parameter name="default">honeybee_module</ae:parameter>
            <ae:parameter name="choices">
                <ae:parameter>honeybee_module</ae:parameter>
                <ae:parameter>omgomg1234</ae:parameter>
                <ae:parameter>trololo</ae:parameter>
            </ae:parameter>
            <ae:parameter name="ignore_choices">
                <ae:parameter>unavailable_skeleton</ae:parameter>
            </ae:parameter>
            <ae:parameter name="attempts">2</ae:parameter>
            <ae:parameter name="introduction"><![CDATA[
Some <info>informational</info> text presented only once before the first display of a question.
]]></ae:parameter>
        </ae:parameter>
    </validator>
 */
class ConsoleDialogValidator extends AgaviValidator
{
    protected $attempt;
    protected $choices;
    protected $ignore_choices;
    protected $confirm;
    protected $data;
    protected $default;
    protected $default_parameters;
    protected $default_question;
    protected $delegate_validator_class;
    protected $dialog;
    protected $input;
    protected $introduction;
    protected $max_attempts;
    protected $original_argument;
    protected $output;
    protected $question;
    protected $random_id;
    protected $select;
    protected $validation_manager;

    /**
     * Validates the initially given data and if that fails
     * asks N times for valid new data instead.
     */
    protected function validate()
    {
        $this->original_argument = $this->getArgument();
        $this->data = $this->getData($this->original_argument);

        $this->setupProperties();

        // validate and ask for new value as long as needed/configured
        while ($this->attempt++ <= $this->max_attempts) {
            $severity = $this->validateViaDelegateValidator($this->data);

            if ($severity === AgaviValidator::SUCCESS) {
                break; // yeah! valid value was given
            }

            $continue = $this->askForValue();
            if (!$continue) {
                $this->throwError('no_choice');
                break; // hmm! no valid options..
            }
        }

        $this->output->writeln('');

        fclose($this->input);

        $success = $severity === AgaviValidator::SUCCESS;

        // only export (new) data under original argument name when validation succeeded
        if ($success) {
            $this->export($this->data, $this->original_argument);
        }

        return $success;
    }

    /**
     * Asks for input of a valid new value for the original argument.
     */
    protected function askForValue()
    {
        $this->printIntroduction();

        if ($this->confirm) {
            // a simple yes/no confirmation dialog
            $this->data = $this->dialog->ask(
                new ArgvInput,
                $this->output,
                new ConfirmationQuestion($this->question, $this->default)
            );
        } elseif ($this->select) {
            // selection dialog to choose values from a list of choices
            $this->choices = array_unique(array_values(array_diff($this->choices, $this->ignore_choices)));
            if (empty($this->choices)) {
                return false;
            }
            $this->data = $this->dialog->ask(
                new ArgvInput,
                $this->output,
                new ChoiceQuestion($this->question, $this->choices, $this->default)
            );
        } else {
            // default behavior: ask for a valid value and allow autocompletion
            $this->data = $this->dialog->ask(
                new ArgvInput,
                $this->output,
                new Question($this->question, $this->default)
            );
        }

        return true;
    }

    /**
     * Prints introductory text on first input attempt.
     */
    protected function printIntroduction()
    {
        $this->output->writeln('');

        if (($this->attempt === 1) && (false !== $this->introduction)) {
            $this->output->writeln($this->introduction);
            $this->output->writeln('');
        }
    }

    /**
     * Executes the delegate validator and returns the validation result.
     *
     * @return int AgaviConfig severity constant value
     */
    protected function validateViaDelegateValidator($data)
    {
        $argument_name = $this->random_id . '_' . $this->original_argument . '_' . $this->attempt;

        // add new proxy argument to the validation data
        $this->validationParameters->setParameter($argument_name, $data);

        $this->default_parameters['name'] .= '_' . $this->attempt;

        $validator = $this->validation_manager->createValidator(
            $this->delegate_validator_class,
            array($argument_name),
            $this->errorMessages,
            $this->default_parameters
        );

        $severity = $validator->execute($this->validationParameters);

        // mark succeeded proxy argument as error to hide it from request data and validation report
        if ($severity === AgaviValidator::SUCCESS) {
            $this->getParentContainer()->addArgumentResult(
                new AgaviValidationArgument($argument_name),
                AgaviValidator::ERROR,
                $validator
            );
        } else {
            $this->output->writeln('');
            $this->output->writeln('Invalid value given for: ' . $this->original_argument);
            $incidents = $this->validation_manager->getValidatorIncidents($this->default_parameters['name']);
            foreach ($incidents as $incident) {
                foreach ($incident->getErrors() as $error) {
                    $this->output->writeln('- ' . $error->getMessage());
                }
            }
        }

        return $severity;
    }

    /**
     * Creates dialog helper and default settings necessary and sets them as
     * member properties of this class.
     */
    protected function setupProperties()
    {
        $vm = $this->getParentContainer();
        while (get_class($vm) !== 'AgaviValidationManager') {
            $vm = $vm->getParentContainer();
        }
        $this->validation_manager = $vm;

        // this is necessary as getting the default STDIN stream would break
        // agavi input/output later on (as php doesn't like getting it twice?)
        $this->input = fopen('/dev/tty', 'r');

        $this->output = new ConsoleOutput;
        $helper_set = new HelperSet([ new FormatterHelper ]);
        $this->dialog = new QuestionHelper($this->output);
        $this->dialog->setHelperSet($helper_set);
        $this->dialog->setInputStream($this->input);

        $this->random_id = uniqid('proxy_', true);
        $this->attempt = 0;

        $this->delegate_validator_class = $this->getParameter('dialog[validator]', "\AgaviIssetValidator");
        $this->default_parameters = $this->getParameters();
        $this->default_parameters['severity'] = 'none'; // silent errors for proxy validation attempts
        $this->default_parameters['name'] .= '_' . $this->random_id;

        // unset some parameters that are not necessary for the proxy validator
        unset($this->default_parameters['provides']);
        unset($this->default_parameters['depends']);
        unset($this->default_parameters['class']);
        unset($this->default_parameters['dialog']);

        $this->introduction = $this->getParameter('dialog[introduction]', false);
        $this->max_attempts = $this->getParameter('dialog[attempts]', 3);
        $this->choices = $this->getParameter('dialog[choices]', array());
        $this->ignore_choices = (array)$this->getParameter('dialog[ignore_choices]', array());
        $this->default = $this->getParameter('dialog[default]', null);
        $this->confirm = (bool)$this->getParameter('dialog[confirm]', false);
        $this->select = (bool)$this->getParameter('dialog[select]', false);

        $default_value_as_text = (string)$this->default;
        if ($this->confirm) {
            $default = (bool) ($this->default === null ? true : $this->default);
            $default_value_as_text = ($default ? 'y' : 'n');
        }

        $this->default_question = 'Input value for "' . $this->original_argument . '"';
        $this->question = '<question>' . $this->getParameter('dialog[question]', $this->default_question);
        if ($this->confirm) {
            $this->question .= '</question> (Type [y/n], default=' . $default_value_as_text . ')';
        } elseif (null !== $this->default) {
            $this->question .= '</question> (Default: ' . $default_value_as_text . ')';
        } else {
            $this->question .=  '</question>';
        }
        $this->question .= ': ';
    }

    /**
     * Always trigger the validator - even if the argument wasn't provided.
     */
    protected function checkAllArgumentsSet($throw_error = true)
    {
        return true;
    }
}
