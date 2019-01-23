<?php

namespace Honeygavi\App\Base;

use AgaviConfig;
use AgaviConsoleResponse;
use AgaviExecutionContainer;
use AgaviParameterHolder;
use AgaviRequestDataHolder;
use AgaviView;
use AgaviViewException;
use AgaviWebResponse;
use Exception;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\Config\Settings;
use Honeybee\Projection\ProjectionInterface;
use Honeygavi\App\ActionPack\Create\CreateInputView;
use Honeygavi\App\ActionPack\Resource\Modify\ModifyInputView;
use Honeygavi\Logging\LogTrait;
use Honeygavi\Ui\Activity\PrimaryActivityMap;
use Honeygavi\Ui\Activity\Url;
use Honeygavi\Ui\OutputFormat\OutputFormatInterface;
use ReflectionClass;

/**
 * Base view for all the views of the application.
 */
class View extends AgaviView
{
    use LogTrait;

    const ATTRIBUTE_PAGE_TITLE = '_page_title';
    const ATTRIBUTE_BREADCRUMBS = '_breadcrumbs';
    const ATTRIBUTE_BREADCRUMBS_TITLE = '_breadcrumbs_title';
    const ATTRIBUTE_BROWSER_TITLE = '_browser_title';
    const ATTRIBUTE_GLOBAL_CSS = '_globalcss';
    const ATTRIBUTE_RENDERED_NAVIGATION = '_rendered_navigation';
    const ATTRIBUTE_RENDERED_SUBHEADER_ACTIVITIES = '_rendered_subheader_activities';
    const ATTRIBUTE_RENDERED_PRIMARY_ACTIVITIES = '_rendered_primary_activities';
    const ATTRIBUTE_IS_SLOT = '_is_slot';
    const ATTRIBUTE_LAYOUT = '_layout';
    const ATTRIBUTE_VIEW_SETTINGS = '_view_settings';

    const JSON_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Name of the default layout to use for slots.
     */
    const DEFAULT_SLOT_LAYOUT_NAME = 'slot';

    /**
     * Holds a reference to the current routing object. May be a more concrete
     * instance like an \AgaviConsoleRouting or \AgaviWebRouting.
     *
     * @var \AgaviRouting
     */
    protected $routing;

    /**
     * Holds a reference to the current request object. May be a more concrete
     * instance like an \AgaviConsoleRequest or \AgaviWebRequest.
     *
     * @var \AgaviRequest
     */
    protected $request;

    /**
     * Holds a reference to the translation manager.
     *
     * @var \AgaviTranslationManager
     */
    protected $translation_manager;

    /**
     * Holds a reference to the user for the current session.
     *
     * @var \AgaviUser
     */
    protected $user;

    /**
     * Holds a reference to the current agavi controller.
     *
     * @var \AgaviController
     */
    protected $controller;

    /**
     * Initialize the view and set default member variables available in all
     * views.
     *
     * @param \AgaviExecutionContainer $container
     */
    public function initialize(AgaviExecutionContainer $container)
    {
        parent::initialize($container);

        $this->controller = $this->getContext()->getController();
        $this->routing = $this->getContext()->getRouting();
        $this->request = $this->getContext()->getRequest();
        $this->translation_manager = $this->getContext()->getTranslationManager();
        $this->user = $this->getContext()->getUser();

        $this->preference_manager = null; // @todo bauen!
    }

    protected function getPreferences()
    {
        // @todo Honeygavi\View\PreferenceContainer
        return $this->preference_manager->getPreferences($this->getPreferencesScope());
    }

    protected function getPreferencesScope()
    {
        return sprintf(
            'app.module.%s.%s.%s',
            $this->container->getViewModuleName(),
            $this->container->getActionName(),
            $this->container->getViewName()
        );
    }

    /**
     * @return \Honeybee\ServiceLocatorInterface
     */
    protected function getServiceLocator()
    {
        return $this->getContext()->getServiceLocator();
    }

    /**
     * @todo use twig or introduce different escaping strategies for html/js/html_attributes/css etc.
     *
     * @return string htmlspecialchars encoded string
     */
    protected function escape($string)
    {
        return StringToolkit::escapeHtml($string);
    }

    /**
     * @return bool whether the current execution container is a slot or not.
     */
    protected function isSlot()
    {
        return (bool)$this->getContainer()->getParameter('is_slot', false);
    }

    /**
     * @return bool whether the current execution container is the result of an action being forwarded
     */
    protected function wasForwarded()
    {
        return $this->getContainer()->getParameter('is_forward', false);
    }

    /**
     * Convenience method to configure the layout and some defaults like a page title when using the html output type.
     *
     * @param \AgaviRequestDataHolder $request_data
     * @param string $layout_name layout name from output_types.xml file
     * @param bool $add_fpf_info enables the addition of the validation report to the top-most container for the FPF
     */
    protected function setupHtml(AgaviRequestDataHolder $request_data, $layout_name = null, $add_fpf_info = true)
    {
        $this->setAttribute(static::ATTRIBUTE_IS_SLOT, $this->isSlot());

        if ($layout_name === null && $this->isSlot()) {
            $layout_name = self::DEFAULT_SLOT_LAYOUT_NAME;
        } else {
            $this->prepareTemplateAttributes($request_data);
        }

        if ($add_fpf_info && $this->wasForwarded()) {
            $this->addValidationReportToRequestForFpf();
        }

        $layout_parameters = $this->loadLayout($layout_name);

        if (!isset($layout_parameters['layout_template'])) {
            $layout = $this->getFallbackLayoutTemplate();
        } else {
            $layout = $layout_parameters['layout_template'];
        }

        if (!$this->hasAttribute(static::ATTRIBUTE_LAYOUT)) {
            $this->setAttribute(static::ATTRIBUTE_LAYOUT, $layout);
        }

        $this->prepareViewConfigSlots();
    }

    protected function prepareTemplateAttributes(AgaviRequestDataHolder $request_data)
    {
        if (!$this->hasAttribute(static::ATTRIBUTE_PAGE_TITLE)) {
            $this->setAttribute(static::ATTRIBUTE_PAGE_TITLE, $this->getPageTitle());
        }

        if (!$this->hasAttribute(static::ATTRIBUTE_BROWSER_TITLE)) {
            $this->setAttribute(
                static::ATTRIBUTE_BROWSER_TITLE,
                $this->getAttribute(static::ATTRIBUTE_PAGE_TITLE, '') . ' | ' .
                AgaviConfig::get('core.app_name', 'Honeybee')
            );
        }

        if (!$this->hasAttribute(static::ATTRIBUTE_GLOBAL_CSS)) {
            $this->setAttribute(static::ATTRIBUTE_GLOBAL_CSS, $this->getGlobalCss());
        }

        if (!$this->hasAttribute(static::ATTRIBUTE_RENDERED_NAVIGATION)) {
            $this->setAttribute(
                static::ATTRIBUTE_RENDERED_NAVIGATION,
                $this->isSlot() ? '' : $this->getRenderedNavigation()
            );
        }

        if (!$this->hasAttribute(static::ATTRIBUTE_BREADCRUMBS)) {
            $this->setAttribute(static::ATTRIBUTE_BREADCRUMBS, $this->getRenderedBreadcrumbs());
        }

        if (!$this->hasAttribute(static::ATTRIBUTE_BREADCRUMBS_TITLE)) {
            $this->setAttribute(static::ATTRIBUTE_BREADCRUMBS_TITLE, $this->getBreadcrumbsTitle());
        }

        $view_config_service = $this->getServiceLocator()->getViewConfigService();
        $view_config = $view_config_service->getViewConfig($this->getViewScope());
        $view_settings = $view_config->getSettings();
        if (!$this->hasAttribute(static::ATTRIBUTE_VIEW_SETTINGS)) {
            $this->setAttribute(static::ATTRIBUTE_VIEW_SETTINGS, $view_settings);
        }
    }

    /**
     * Sets the given data on the given form. This replaces all data that may
     * exist in the request for that form.
     *
     * @see http://mivesto.de/agavi/agavi-faq.html#validation_8 for more info.
     *
     * @param string $form_id id of the html form that should be populated
     * @param array $data data to set for the given form (html input element names and values)
     *
     * @return void
     */
    protected function populateForm($form_id, $data)
    {
        $populate = $this->request->getAttribute('populate', 'org.agavi.filter.FormPopulationFilter', array());
        $populate[$form_id] = new AgaviParameterHolder($data);
        $this->request->setAttribute('populate', $populate, 'org.agavi.filter.FormPopulationFilter');
    }

    /**
     * Adds the validation report to the current request to retain error messages
     * when forwarding (like <code>return $this->createForwardContainer('Foo', 'Bar');</code>).
     *
     * Background: The \AgaviFormPopulationFilter gets the validation report
     * from the AgaviExecutionContainer of the initial request (the main agavi
     * action called) and uses that report to fill forms with data taken from
     * the request (form fields and values).
     *
     * If your forms are not populated automatically, check that you have an
     * id and an action attribute on the form element and call this method in
     * the view of the action that was being forwarded to internally.
     *
     * @return void
     */
    protected function addValidationReportToRequestForFpf()
    {
        $this->request->setAttribute(
            'validation_report',
            $this->getContainer()->getValidationManager()->getReport(),
            'org.agavi.filter.FormPopulationFilter'
        );
    }

    /**
     * Return any reported validation error messages from the validation manager.
     *
     * @return array
     */
    protected function getErrorMessages()
    {
        $errors = [];

        foreach ($this->getContainer()->getValidationManager()->getErrorMessages() as $error_message) {
            $errors[] = $error_message['message'];
        }

        return $errors;
    }

    /**
     * Renders the given subject via the renderer service. A renderer config
     * can be provided or will be read from the views xml file for the current
     * output type automatically if defined.
     *
     * The renderer service uses a renderer locator to find renderers for the
     * given subject. That renderer may be specified in the output_formats xml
     * file for each output format. Output formats should match Agavi's output
     * type names by default to ease configuration of stuff. Output formats are
     * not the same as output types atm as you may want to render json islands
     * in html output etc.
     *
     * By default the renderer will get a view template name that is the same
     * as the current view scope. Thus a defined scope in the views xml file
     * results of being searched for a view template xml entry of that name.
     * The view template lookup tries to find an output type specific view
     * template by appending the output type's name to the scope key (e.g.
     * 'scope.key' leads to lookups for 'scope.key.html' for html output). It
     * falls back to the output type unspecific view template name matching
     * the view scope.
     *
     * @param mixed $subject the object or data to render
     * @param array $render_settings runtime settings to be given to the renderer on render()
     * @param mixed $renderer_config_or_name a config or a renderer name defined in views xml for current output type
     * @param array $additional_payload associative array with data to give as payload to the applied renderer
     * @param string $view_scope name of the scope to use; defaults to the current action/view's scope key
     * @param string $output_format_name name of the output format to render for; defaults to currently used one
     *
     * @return mixed the result the applied renderer provided on execution
     */
    protected function renderSubject(
        $subject,
        array $render_settings = [],
        $renderer_config_or_name = null,
        array $additional_payload = [],
        $view_scope = null,
        $output_format_name = null
    ) {
        $output_format = $this->getOutputFormat($output_format_name);

        if ($view_scope === null) {
            $view_scope = $this->getViewScope();
        }

        $view_config_service = $this->getServiceLocator()->getViewConfigService();

        $default_data = [
            'view_scope' => $view_scope,
        ];

        // try to determine a default renderer-config-name from the views's output format section
        $renderer_config = $renderer_config_or_name;
        if (is_null($renderer_config_or_name)) {
            $renderer_config = $view_config_service->getRendererConfig(
                $view_scope,
                $output_format,
                $subject,
                $default_data
            );
        } elseif (is_string($renderer_config_or_name)) {
            if (!empty($renderer_config_or_name)) {
                $renderer_config = $view_config_service->getRendererConfig(
                    $view_scope,
                    $output_format,
                    $renderer_config_or_name,
                    $default_data
                );
            } else {
                $renderer_config = new ArrayConfig($default_data);
            }
        } elseif (is_array($renderer_config_or_name)) {
            $renderer_config = new ArrayConfig(array_merge($default_data, $renderer_config_or_name));
        } elseif (is_object($renderer_config_or_name)) {
            if (!$renderer_config_or_name instanceof ConfigInterface) {
                throw new RuntimeError(sprintf(
                    'Renderer config must implement %s; "%s" provided.',
                    ConfigInterface::class,
                    get_class($renderer_config_or_name)
                ));
            }
        } else {
            throw new RuntimeError('Renderer config must implement ConfigInterface, be a name to lookup or an array');
        }

        $renderer_service = $this->getServiceLocator()->getRendererService();

        return $renderer_service->renderSubject(
            $subject,
            $output_format,
            $renderer_config,
            $additional_payload,
            new Settings($render_settings)
        );
    }

    protected function prepareViewConfigSlots($view_scope = null)
    {
        $view_config_service = $this->getServiceLocator()->getViewConfigService();

        if ($view_scope === null) {
            $view_scope = $this->getViewScope();
        }

        $view_config = $view_config_service->getViewConfig($view_scope);

        $slots_config = $view_config->getSlots();
        foreach ($slots_config as $slot_name => $slot_settings) {
            if ($slot_settings->get('enabled', true)) {
                $layer_name = $slot_settings->get('layer_name', 'content');

                $module_name = $slot_settings->get('module_name');
                $action_name = $slot_settings->get('action_name');
                $arguments = (array)$slot_settings->get('parameters', []);
                $output_type = $slot_settings->get('output_type');
                $request_method = $slot_settings->get('request_method');

                $slot_execution_container = $this->createSlotContainer(
                    $module_name,
                    $action_name,
                    $arguments,
                    $output_type,
                    $request_method
                );

                $this->getLayer($layer_name)->setSlot($slot_name, $slot_execution_container);
            }
        }
    }

    protected function prepareViewConfigSlot($slot_name, $view_scope = null)
    {
        $view_config_service = $this->getServiceLocator()->getViewConfigService();

        if ($view_scope === null) {
            $view_scope = $this->getViewScope();
        }

        $view_config = $view_config_service->getViewConfig($view_scope);
        $slots_config = $view_config->getSlots();

        if ($slots_config->has($slot_name)) {
            $slot_settings = $slots_config->get($slot_name);
            if ($slot_settings->get('enabled', true)) {
                $layer_name = $slot_settings->get('layer_name', 'content');

                $module_name = $slot_settings->get('module_name');
                $action_name = $slot_settings->get('action_name');
                $arguments = (array)$slot_settings->get('parameters', []);
                $output_type = $slot_settings->get('output_type');
                $request_method = $slot_settings->get('request_method');

                $slot_execution_container = $this->createSlotContainer(
                    $module_name,
                    $action_name,
                    $arguments,
                    $output_type,
                    $request_method
                );

                $this->getLayer($layer_name)->setSlot($slot_name, $slot_execution_container);
            }
        }
    }

    protected function getViewScope()
    {
        if ($this->hasAttribute('view_scope')) {
            return $this->getAttribute('view_scope');
        }

        return $this->getScopeKey();
    }

    /**
     * Return the output format that is used for the current output type or the one specified by name. That is,
     * it uses the parameter 'output_format' on the current output type or tries to get the output format that matches
     * the name of the current output type or tries to get the output format given via method argument name.
     *
     * @param string $output_format_name name of output format to return; defaults to the one of the current output type
     *
     * @return OutputFormatInterface of the current output type or the given name
     *
     * @throws RuntimeError when output_format for the given name or the current output type is not configured
     */
    protected function getOutputFormat($output_format_name = null)
    {
        $output_format_service = $this->getServiceLocator()->getOutputFormatService();

        // try to get name from the current output type
        if ($output_format_name === null) {
            $current_output_type = $this->getContainer()->getOutputType();
            $output_format_name = $current_output_type->getParameter('output_format', $current_output_type->getName());
        }

        $output_format = $output_format_service->getOutputFormat($output_format_name);

        if (empty($output_format)) {
            throw new RuntimeError('Output format is not configured: ' . $output_format_name);
        }

        return $output_format;
    }

    /**
     * Tries to find a template for the given resource by inspecting the current view
     * and the domain object's workflow state.
     *
     * That is, an "InputView" with an resource of workflow step "inactive" results in
     * a template of ".../Inactive_Input" that can then be set via "setTemplate()".
     *
     * Returns the first found template that matches the view's name and has a file extension
     * of '.haml', '.twig' or '.php'.
     *
     * @param ProjectionInterface $resource domain object with State/Step
     *
     * @return string|false template path/name to use; false if no matching template file was found
     */
    protected function getCustomTemplate(ProjectionInterface $resource)
    {
        $view_class = new ReflectionClass($this);
        $view_class_dir = dirname($view_class->getFilename());

        $state = $resource->getWorkflowState();
        $class_file_name = basename($view_class->getFilename());
        $class_name = str_replace('View.class.php', '', $class_file_name);
        $template_name = sprintf('%s_%s', ucfirst($state), $class_name);
        $template_extensions = array('.haml', '.twig', '.php');

        $custom_template = false;
        foreach ($template_extensions as $template_extension) {
            $template_file = $template_name . $template_extension;
            $template_path = $view_class_dir . DIRECTORY_SEPARATOR . $template_file;
            if (is_readable($template_path)) {
                $custom_template = $view_class_dir . DIRECTORY_SEPARATOR . $template_name;
                break;
            }
        }

        return $custom_template;
    }

    /**
     * Outputs given message on STDOUT and sets the given shell exit code on the response
     *
     * @param string $message message to output
     * @param int $exit_code shell exit code to use; defaults to 0 for success.
     *
     * @return void|string usually nothing, but error message in case of non-cli SAPI and undefined STDOUT constant
     *
     * @throws \Exception when current response ist not an instance of AgaviConsoleResponse
     */
    protected function cliMessage($message, $exit_code = 0)
    {
        if (!$this->getResponse() instanceof AgaviConsoleResponse) {
            throw new Exception(
                "The current response must be an instance of \AgaviConsoleResponse." .
                "Please don't use this method for non-console contexts."
            );
        }

        if (!$this->getResponse()->getParameter('append_eol', true)) {
            $message .= PHP_EOL;
        }

        $this->getResponse()->setExitCode($exit_code);

        /*
         * we just send stuff to STDOUT as AgaviResponse::sendContent() uses fpassthru which
         * does not allow us to give the handle to Agavi via $rp->setContent() or return $handle
         *
         * notice though, that the shell exit code will still be set correctly
         */
        if (php_sapi_name() === 'cli' && defined('STDOUT')) {
            fwrite(STDOUT, $message);
        } else {
            return $message;
        }
    }

    /**
     * Outputs given message on STDERR and sets the given shell exit code on the response
     *
     * @param string $error_message message to output
     * @param int $exit_code shell exit code to use; defaults to 1 for general errors
     * @param bool $with_validation_errors whether to output the validation error messages as well
     *
     * @return void|string usually nothing, but error message in case of non-cli SAPI and undefined STDERR constant
     *
     * @throws \Exception when current response ist not an instance of AgaviConsoleResponse
     */
    public function cliError($error_message, $exit_code = 1, $with_validation_errors = false)
    {
        if ($with_validation_errors) {
            $errors = $this->getErrorMessages();
            if (count($errors) > 0) {
                $error_message .= PHP_EOL . '- ' . implode($errors, PHP_EOL . '- ');
            }
        }

        if (!$this->getResponse() instanceof AgaviConsoleResponse) {
            throw new Exception(
                "The current response must be an instance of \AgaviConsoleResponse." .
                "Please don't use this method for non-console contexts."
            );
        }

        if (!$this->getResponse()->getParameter('append_eol', true)) {
            $error_message .= PHP_EOL;
        }

        $this->getResponse()->setExitCode($exit_code);

        /*
         * we just send stuff to STDERR as AgaviResponse::sendContent() uses fpassthru which
         * does not allow us to give the handle to Agavi via $rp->setContent() or return $handle
         *
         * notice though, that the shell exit code will still be set correctly
         */
        if (php_sapi_name() === 'cli' && defined('STDERR')) {
            fwrite(STDERR, $error_message);
            fclose(STDERR);
        } else {
            return $error_message;
        }
    }

    /**
     * Handles non-existing methods. This includes mainly the not implemented
     * handling of certain output types.
     *
     * @param string $method_name
     * @param array $arguments
     *
     * @throws AgaviViewException with different messages
     */
    public function __call($method_name, $arguments)
    {
        if (preg_match('~^(execute|setup)([A-Za-z_]*)$~', $method_name, $matches)) {
            /*
             * The current output type is not supported by the current view.
             * If "core.debug" is false (e.g. production environment) we log
             * the error and respond with a generic HTTP status 406 error
             * instead of throwing an exception that leads to HTTP status 500.
             *
             * @todo Would a 500 error actually be more correct? Yes maybe, but we executed the action. Hm. Meh.
             */
            if (!AgaviConfig::get('core.debug', false)) {
                $uri = '';
                if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
                    $uri = $_SERVER['REQUEST_URI'];
                } else {
                    $uri = $this->routing->getInput();
                }
                $this->logError(
                    sprintf(
                        'The view "%1$s" does not implement an "%2$s()" method. ' .
                        'Sending a generic 406 response (as debug is false). ' .
                        'The view should implement "%1$s::%2$s()" or handle this ' .
                        'situation in one of the base views (e.g. "%3$s"). URI="%4$s".',
                        get_class($this),
                        $method_name,
                        get_class(),
                        $uri
                    )
                );

                $response = $this->getResponse();
                if ($response instanceof AgaviWebResponse) {
                    $response->setHttpHeader('Content-Type', 'application/vnd.error+json');
                    $response->setHttpStatusCode(406); // Not Acceptable
                } elseif ($response instanceof AgaviConsoleResponse) {
                    $response->setExitCode(70); // 70 ("internal software error") instead of 1 ("general error")
                }

                return json_encode(
                    array(
                        'logref' => 'ot_error406',
                        'message' => 'There was an error trying to support the accepted media type.'
                    ),
                    JSON_FORCE_OBJECT
                );
            }

            $this->throwOutputTypeNotImplementedException();
        }

        throw new AgaviViewException(
            sprintf(
                'The view "%1$s" does not implement an "%2$s()" method. Please ' .
                'implement "%1$s::%2$s()" or handle this situation in one of the base views (e.g. "%3$s").',
                get_class($this),
                $method_name,
                get_class()
            )
        );
    }

    /**
     * Convenience method for throwing an exception that notifies the caller
     * about the not implemented output type handling method for this view.
     * This method is called via __call() overrider in this class.
     *
     * @throws \AgaviViewException
     */
    protected function throwOutputTypeNotImplementedException()
    {
        throw new AgaviViewException(
            sprintf(
                'The view "%1$s" does not implement an "execute%3$s()" method to serve the ' .
                'output type "%2$s". Please implement "%1$s::execute%3$s()" or handle this ' .
                'situation in one of the base views. Handling in a module\'s or application\'s base ' .
                'view may include throwing 40x errors or displaying further explanations about ' .
                'how to react as a user or developer in that case.',
                get_class($this),
                $this->container->getOutputType()->getName(),
                ucfirst(strtolower($this->container->getOutputType()->getName())),
                get_class()
            )
        );
    }

    /**
     * If developers try to use the execute method in views instead of creating
     * an output type specific handler they will get a fatal error. If they call
     * this method directly we try to help them with an exception.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public final function execute(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        throw new AgaviViewException(
            sprintf(
                'There should be no "execute()" method in "%1$s". Views deal ' .
                'with output types and should therefore implement specific ' .
                '"execute<OutputTypeName>()" methods. It is recommended that ' .
                'you either implement "execute%3$s()" for the current output type ' .
                '"%2$s" and all other supported output types in each of your views ' .
                'or implement more general fallbacks in the module\'s or applications\'s base views (e.g. "%4$s").',
                get_class($this),
                $this->container->getOutputType()->getName(),
                ucfirst(strtolower($this->container->getOutputType()->getName())),
                get_class()
            )
        );
    }

    /**
     * @return string scope key for the current view (e.g. honeybee.system_account.user.resource.modify)
     */
    protected function getScopeKey()
    {
        // view class name, e.g. "Honeybee_SystemAccount_User_ApiLogin_ApiLoginErrorView"
        $class_name_parts = explode('_', static::CLASS);
        $short_name = implode('.', array_map([StringToolkit::CLASS, 'asSnakeCase' ], $class_name_parts));

        // e.g. honeybee.system_account.user.api_login
        return preg_replace('~\.[a-z\_]+view$~', '', $short_name);
    }

    protected function getGlobalCss()
    {
        // view's php class name, e.g. "Honeybee_SystemAccount_User_ApiLogin_ApiLoginErrorView"
        $class_name_parts = explode('_', static::CLASS);

        // honeybee
        $vendor = StringToolkit::asSnakeCase(array_shift($class_name_parts));

        $snake_parts = array_map([StringToolkit::CLASS, 'asSnakeCase' ], $class_name_parts);

        $short_name = implode('-', $snake_parts);

        // module-Honeybee_SystemAccount
        $module = 'module-' . $this->getContainer()->getModuleName();

        // some-foo OR honeybee-system_account-user
        $prefix = $this->getTranslationDomainPrefix('-');

        // honeybee-system_account-user-api_login
        $action = preg_replace('~\-[a-z\_]+view$~', '', $vendor . '-' . $short_name);

        // view-api_login
        $view_action = 'view' . str_replace($prefix, '', $action);
        if ($this instanceof CreateInputView) {
            $view_action .= ' view-create';
        } elseif ($this instanceof ModifyInputView) {
            $view_action .= ' view-resource-modify';
        }
        // api_login_error_view
        $specific_view = end($snake_parts);

        return implode(
            ' ',
            [
                'view',
                $vendor,
                $module,
                $action,
                $view_action,
                $specific_view,
            ]
        );
    }

    protected function getRenderedNavigation()
    {
        if (!$this->user->isAuthenticated()) {
            // no menu for users that are not logged in
            return '';
        }

        $navigation_service = $this->getServiceLocator()->getNavigationService();

        $navigation = $navigation_service->getNavigation($this->getNavigationName());

        return $this->renderSubject($navigation);
    }

    protected function getNavigationName()
    {
        return $this->getServiceLocator()->getNavigationService()->getDefaultNavigationName();
    }

    protected function getPageTitle()
    {
        $prefix = $this->getTranslationDomainPrefix();

        $action = str_replace($prefix . '.', '', $this->getViewScope());

        $translation_domain = $prefix . '.views';

        $page_title_key = $action . '.page_title';

        $view_title = $this->getServiceLocator()->getTranslator()->translate($page_title_key, $translation_domain);

        return $view_title;
    }

    protected function getRenderedBreadcrumbs()
    {
        if (!$this->user->isAuthenticated()) {
            // no breadcrumbs for users that are not logged in
            return '';
        }

        $breadcrumbs_activities = $this->getBreadcrumbsActivities();

        // render activities
        $rendererd_breadcrumbs = [];
        foreach ($breadcrumbs_activities as $breadcrumbs_activity) {
            $rendererd_breadcrumbs[] = $this->renderSubject($breadcrumbs_activity);
        }

        return $rendererd_breadcrumbs;
    }

    protected function getBreadcrumbsActivities()
    {
        return [];
    }

    protected function getBreadcrumbsTitle()
    {
        return $this->getPageTitle();
    }

    protected function getTranslationDomainPrefix($join_char = '.')
    {
        $class_name_parts = explode('_', static::CLASS);

        $vendor = StringToolkit::asSnakeCase(array_shift($class_name_parts));
        $package = StringToolkit::asSnakeCase(array_shift($class_name_parts));
        $resource = StringToolkit::asSnakeCase(array_shift($class_name_parts));

        $projection_type_map = $this->getServiceLocator()->getProjectionTypeMap();
        if ($projection_type_map->filterByPrefix($vendor . '.' . $package . '.' . $resource)->isEmpty()) {
            return $vendor . $join_char . $package;
        } else {
            return $vendor . $join_char . $package . $join_char . $resource;
        }
    }

    protected function getFallbackLayoutTemplate()
    {
        return [ 'html/layout/MasterLayout.twig', 'html/layout/SlotLayout.twig' ];
    }

    /** setSubheaderActivities & setPrimaryActivities load activities for the actual layout.
      * Those are convenient here but should not be part of the base-(non-opinionated)-view.
      *
      * @todo Move them in a intermediate/opinionated View that the ActionPack views could extend
      */
    protected function setSubheaderActivities(AgaviRequestDataHolder $request_data)
    {
        $container_scope_key = $this->getViewScope() . '.subheader_activities';
        $activity_service = $this->getServiceLocator()->getActivityService();

        $subheader_activities_container = $activity_service->getContainer($container_scope_key);
        $subheader_activities = $subheader_activities_container->getActivityMap();

        $rendered_subheader_activities = $this->renderSubject(
            $subheader_activities,
            [],
            'subheader_activities'
        );

        $this->setAttribute(static::ATTRIBUTE_RENDERED_SUBHEADER_ACTIVITIES, $rendered_subheader_activities);

        return $rendered_subheader_activities;
    }

    protected function setPrimaryActivities(AgaviRequestDataHolder $request_data)
    {
        $container_scope_key = $this->getViewScope() . '.primary_activities';
        $activity_service = $this->getServiceLocator()->getActivityService();

        $primary_activities_container = $activity_service->getContainer($container_scope_key);
        $items = [];
        foreach ($primary_activities_container->getActivityMap()->getItems() as $item) {
            // the default skeleton has "back_to_collection" activities in XML configured. we try to return to the
            // last seen collection view for the current resource type with all sort/filter/etc previously set
            if ($item->getName() === 'back_to_collection' && $this->hasAttribute('resource')) {
                $prefix = $this->getAttribute('resource')->getType()->getPrefix();
                $url = $this->user->getAttribute($prefix ?? 'some-non-existant-key', 'collection_views');
                if (!empty($url)) {
                    $item = $item->withUrl(Url::createUri($url));
                }
            }
            $items[] = $item;
        }
        $primary_activities = new PrimaryActivityMap($items);

        $rendered_primary_activities = $this->renderSubject(
            $primary_activities,
            [],
            'primary_activities'
        );

        $this->setAttribute(static::ATTRIBUTE_RENDERED_PRIMARY_ACTIVITIES, $rendered_primary_activities);

        return $rendered_primary_activities;
    }

    protected function getCuries()
    {
        $curie_tpl = AgaviConfig::get('curie_url_tpl', AgaviConfig::get('local.base_href').'honeybee-core/rels/{rel}');

        $links = [
            "curies" => [[
                "name" => $this->getCurieName(),
                "href" => $curie_tpl,
                "templated" => true,
            ]],
        ];

        return $links;
    }

    protected function getCurieName()
    {
        return AgaviConfig::get('app_prefix', 'honeybee');
    }
}
