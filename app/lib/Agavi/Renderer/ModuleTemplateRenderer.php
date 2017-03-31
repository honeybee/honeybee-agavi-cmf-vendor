<?php

namespace Honeygavi\Agavi\Renderer;

use AgaviConfig;
use AgaviContext;
use AgaviFileTemplateLayer; // do not remove!
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Agavi\Renderer\TwigRenderer;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeygavi\Mail\Message;

/**
 * Renders templates with Twig (for modules).
 */
class ModuleTemplateRenderer
{
    /**
     * @var Honeybee\Infrastructure\ArrayConfig with given config
     */
    protected $config;

    /**
     * @param mixed $mixed ArrayConfig instance with settings or array
     *
     * @throws RuntimeError if no valid module of ArrayConfig instance was given
     */
    public function __construct($mixed = [])
    {
        $this->setConfig($mixed);
    }

    /**
     * Change config of this template renderer instance. This is necessary to e.g. change the
     * module specific template lookup patterns by setting a new 'module_name'.
     *
     * @param mixed $mixed array with module_name or module to get that information from
     *
     * @return self instance for fluent api
     */
    public function setConfig($mixed = array())
    {
        if ($mixed instanceof ArrayConfig) {
            $this->config = $mixed;
        } elseif (is_array($mixed)) {
            $this->config = new ArrayConfig($mixed);
        } else {
            throw new RuntimeError(
                'As PHP does not support overloading there is unfortunately no type hint for the correct type of ' .
                'constructor argument. Expected is an ArrayConfig or compatible array with settings.'
            );
        }

        return $this;
    }

    /**
     * This method constructs a Message from the given twig mail template.
     *
     * A valid twig mail template is a file with a '.mail.twig' extension,
     * that has multiple blocks with content:
     *
     * - 'subject' - subject of the message
     * - 'from' - email address of creator
     * - 'sender' - email address of sender (if different from creator)
     * - 'to' - email address of main recipient
     * - 'cc' - email address of carbon-copy receiver
     * - 'bcc' - email address of blind-carbon-copy receiver
     * - 'reply_to' - default email address for replies
     * - 'return_path' - email address to be used for bounce handling
     * - 'body_html' - HTML body part
     * - 'body_text' - plain text body part
     *
     * Only blocks, that exist in the template will be rendered and set.
     *
     * @param mixed $identifier usually the name of the template
     * @param array $variables array of placeholders for the twig template
     * @param array $options array of additional options for the renderer
     *
     * @return Message mail message for further customization
     */
    public function createMessageFromTemplate($identifier, array $variables = array(), array $options = array())
    {
        if (!isset($options['template_extension'])) {
            $options['template_extension'] = '.mail.twig'; //$this->config->get('template_extension', '.mail.twig');
        }

        if (!isset($options['add_agavi_assigns'])) {
            $options['add_agavi_assigns'] = $this->config->get('add_agavi_assigns', true);
        }

        if (!$options['add_agavi_assigns']) {
            $twig_template = $this->loadTemplate($identifier, $options);
        } else {
            // add all assigns from the renderer parameters to the variables
            $layer = $this->getLayer($identifier, $options);
            $renderer = $layer->getRenderer();
            $context = AgaviContext::getInstance();
            $assigns = [];
            foreach ($renderer->getParameter('assigns', []) as $item => $var) {
                $getter = 'get' . StringToolkit::asStudlyCaps($item);
                if (is_callable([$context, $getter])) {
                    if (null === $var) {
                        continue;
                    }
                    $assigns[$var] = call_user_func([$context, $getter]);
                }
            }
            $variables = array_merge($variables, $assigns);

            $twig_template = $renderer->loadTemplate($layer);
        }

        $message = new Message();
        if ($twig_template->hasBlock('subject')) {
            $message->setSubject($twig_template->renderBlock('subject', $variables));
        }

        if ($twig_template->hasBlock('body_html')) {
            $message->setBodyHtml($twig_template->renderBlock('body_html', $variables));
        }

        if ($twig_template->hasBlock('body_text')) {
            $message->setBodyText($twig_template->renderBlock('body_text', $variables));
        }

        if ($twig_template->hasBlock('from')) {
            $message->setFrom($twig_template->renderBlock('from', $variables));
        }

        if ($twig_template->hasBlock('to')) {
            $message->setTo($twig_template->renderBlock('to', $variables));
        }

        if ($twig_template->hasBlock('cc')) {
            $message->setCc($twig_template->renderBlock('cc', $variables));
        }

        if ($twig_template->hasBlock('bcc')) {
            $message->setBcc($twig_template->renderBlock('bcc', $variables));
        }

        if ($twig_template->hasBlock('return_path')) {
            $message->setReturnPath($twig_template->renderBlock('return_path', $variables));
        }

        if ($twig_template->hasBlock('sender')) {
            $message->setSender($twig_template->renderBlock('sender', $variables));
        }

        if ($twig_template->hasBlock('reply_to')) {
            $message->setReplyTo($twig_template->renderBlock('reply_to', $variables));
        }

        return $message;
    }

    /**
     * Renders the template given by the identifier with the specified variables.
     * The options may be used to override instantiation settings like 'output_type',
     * 'layout' and 'template_extension' for one time different uses.
     *
     * @param mixed $identifier template name (will be searched for in default template locations)
     * @param array $variables placeholders in key => value form for twig
     * @param array $options settings like 'output_type', 'layout' or 'template_extension'
     *
     * @return string rendered result
     */
    public function render($identifier, array $variables = [], array $options = [])
    {
        $layer = $this->getLayer($identifier, $options);

        return $layer->execute(null, $variables);
    }

    /**
     * Loads the given file from common template paths and returns the loaded Twig_Template.
     *
     * @param mixed $identifier
     * @param array $options settings like 'output_type', 'layout' or 'template_extension' if the config from
     *              instantiation of the service is not sufficient
     *
     * @return Twig_Template instance
     */
    public function loadTemplate($identifier, array $options = [])
    {
        $layer = $this->getLayer($identifier, $options);

        $template = $layer->getRenderer()->loadTemplate($layer);

        return $template;
    }

    /**
     * Returns the first layer from the default or specified output type layout.
     *
     * @param mixed $identifier template name
     * @param array $options settings like 'output_type', 'layout' or 'template_extension' if the config from
     *              instantiation of the service is not sufficient
     *
     * @return AgaviTemplateLayer instance (usually an AgaviFileTemplateLayer instance)
     *
     * @throws RuntimeError in case of missing or wrong settings
     */
    public function getLayer($identifier, array $options = [])
    {
        $output_type_name = isset($options['output_type']) ?
            $options['output_type'] :
            $this->config->get('output_type', 'template');
        $layout_name = isset($options['layout']) ? $options['layout'] : $this->config->get('layout', 'default');
        $extension = isset($options['template_extension']) ?
            $options['template_extension'] :
            $this->config->get('template_extension', '.twig');

        $output_type = AgaviContext::getInstance()->getController()->getOutputType($output_type_name);
        $layout = $output_type->getLayout($layout_name);

        if (empty($layout['layers'])) {
            throw new RuntimeError(
                'No layers found for layout "' . $layout_name . '" on output type "' . $output_type_name . '".'
            );
        }

        $layer_info = array_shift($layout['layers']); // we take the first layer that's available (probably 'content')

        $class_name = isset($layer_info['class']) ? $layer_info['class'] : "AgaviFileTemplateLayer";
        if (!class_exists($class_name)) {
            throw new RuntimeError(
                sprintf(
                    'First layer of layout "%s" on output type "%s" specifies a non-existant class: %s',
                    $layout_name,
                    $output_type_name,
                    $class_name
                )
            );
        }

        $module_name = array_key_exists('module_name', $options) ?
            $options['module_name'] :
            $this->config->get('module_name', null);

        $layer_params = [
            'template' => $identifier,
            'extension' => $extension,
            'output_type' => $output_type_name,
            'module' => $module_name
        ];

        /* hardcore fallback that leads to target paths like
         * app/project/templates/modules/../de_DE/example.twig
         * instead of
         * app/project/templates/modules/${module}/de_DE/example.twig
         */
        if (empty($module_name)) {
            $layer_params['module'] = '..';
            $layer_params['directory'] = AgaviConfig::get('core.template_dir');
        }

        $lookup_paths = [];
        if (isset($layer_info['parameters']['targets'])) {
            $lookup_paths = array_merge($lookup_paths, $layer_info['parameters']['targets']);
        }

        $layer_params['targets'] = $lookup_paths;

        $layer = new $class_name($layer_params);
        $layer->initialize(AgaviContext::getInstance(), $layer_params);

        $renderer_name = isset($layer_info['renderer']) ?
            $layer_info['renderer'] :
            $this->config->get('renderer', 'twig');
        $layer->setRenderer($output_type->getRenderer($renderer_name));

        if (!$layer->getRenderer() instanceof TwigRenderer) {
            throw new RuntimeError(
                sprintf(
                    'The default layer renderer of layout "%s" on output type "%s" is not an instance of the' .
                    'Honeybee TwigRenderer. At the moment only Twig is supported as a renderer for simple templates.',
                    $layout_name,
                    $output_type_name
                )
            );
        }

        return $layer;
    }
}
