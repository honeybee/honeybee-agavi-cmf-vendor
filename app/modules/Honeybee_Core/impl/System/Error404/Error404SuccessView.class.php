<?php

use Honeygavi\Agavi\App\Base\ErrorView;

/**
 * Handles 404 errors for all supported output types by usually logging the
 * matched routes and routing input and returning an appropriate response.
 */
class Honeybee_Core_System_Error404_Error404SuccessView extends ErrorView
{
    const DEFAULT_ERROR_TITLE = '404 Not Found';

    protected function getTitle()
    {
        return $this->getAttribute('_404_title', null, '404 Not Found');
    }

    protected function getMessage()
    {
        return $this->getAttribute('_404_message', null, 'The resource could not be found.');
    }

    protected function getHttpStatusCode()
    {
        return '404';
    }

    protected function getLogref()
    {
        return 'error404';
    }

    /**
     * Handle 404 errors for commandline interfaces by logging matched routes
     * information and displaying a help message with currently configured
     * routes (including information about pattern, parameters, validation and
     * descriptions) to STDERR with an exit code of 1 for the shell.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeConsole(\AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();

        $error_message = '';

        $route_names_array = $this->request->getAttribute('matched_routes', 'org.agavi.routing');
        if (empty($route_names_array)) {
            $error_message .= PHP_EOL . 'No route matched the given command line arguments: ' . $this->routing->getInput() . PHP_EOL;
        }

        $plain_text_message = $this->getPlainTextErrorMessage();

        if (!empty($plain_text_message)) {
            $error_message .= $plain_text_message;
        }

        $error_message .= 'The following routes and parameters are available:' . PHP_EOL . PHP_EOL;

        $all_routes = $this->getRoutes();

        // sort routes by value of the pattern field (case-insensitive alphanumeric)
        $all_pattern = array();
        foreach ($all_routes as $key => $route_info) {
            $all_pattern[$key] = $route_info['pattern'];
        }

        array_multisort($all_pattern, SORT_NATURAL | SORT_FLAG_CASE, $all_routes);

        // create help with parameters/validation and description for each known route
        foreach ($all_routes as $route) {
            if (isset($route['hidden'])) {
                continue; // hide routes from display when 'hidden' parameter is set
            }

            $error_message .= '  ' . $route['pattern'] . PHP_EOL;

            if (isset($route['description'])) {
                $error_message .= '    ' . $route['description'] . PHP_EOL;
            }

            if (!count($route['parameters'])) {
                $error_message .= PHP_EOL;
                continue;
            }

            foreach ($route['parameters'] as $parameter) {
                $has_base_keys = false;

                // set the correct name when the argument has a base
                if (!is_null($parameter['base'])) {
                    $parameter_name = $parameter['base'];

                    // keys of the base are defined as name by the validator
                    if (!is_null($parameter['name']) && !empty($parameter['name'])) {
                        $has_base_keys = true;
                    }
                } else {
                    $parameter_name = $parameter['name'];
                }

                $error_message .= '    -' . $parameter_name . ': ' . $parameter['class'] . ($parameter['required'] == 'true' ? '' : ' (optional)') . PHP_EOL;

                if ($has_base_keys) {
                    $error_message .= '      keys: ' . $parameter['name'] . PHP_EOL;
                }

                // use description parameter from validator if available
                if (isset($parameter['description'])) {
                    $error_message .= '      ' . $parameter['description'] . PHP_EOL;
                }
            }

            $error_message .= PHP_EOL;
        }

        $error_message .= 'Usage: bin/cli <routename> [parameters]' . PHP_EOL;

        return $this->cliError($error_message);
    }

    /**
     * Recursively get all route information for given action and module name.
     *
     * @author Jan Schütze <jans@dracoblue.de>
     *
     * @param string $parent name of parent route
     * @param string $action name of action
     * @param string $module name of module
     *
     * @return array of routes found with pattern, parameters and description
     */
    protected function getRoutes($parent = null, $action = null, $module = null)
    {
        $routes = array();

        foreach ($this->routing->exportRoutes() as $possible_route) {
            if ($possible_route['opt']['parent'] !== $parent) {
                continue ;
            }

            if (!$possible_route['opt']['action']) {
                $possible_route['opt']['action'] = $action;
            }

            if (!$possible_route['opt']['module']) {
                $possible_route['opt']['module'] = $module;
            }

            if ($possible_route['opt']['action'] && $possible_route['opt']['module']) {
                $route = array(
                    'pattern' => $possible_route['opt']['reverseStr'],
                    'parameters' => $this->getParametersForActionAndModule(
                        $possible_route['opt']['action'],
                        $possible_route['opt']['module']
                    )
                );

                if (isset($possible_route['opt']['parameters']['description'])) {
                    $route['description'] = $possible_route['opt']['parameters']['description'];
                }

                if (isset($possible_route['opt']['parameters']['hidden'])) {
                    $route['hidden'] = $possible_route['opt']['parameters']['hidden'];
                }

                $routes[] = $route;
            }

            if (count($possible_route['opt']['childs'])) {
                foreach (
                    $this->getRoutes(
                        $possible_route['opt']['name'],
                        $possible_route['opt']['action'],
                        $possible_route['opt']['module']
                    ) as $sub_route) {
                    $sub_route['pattern'] = $possible_route['opt']['reverseStr'] . $sub_route['pattern'];
                    $routes[] = $sub_route;
                }
            }
        }

        return $routes;
    }

    /**
     * Get validation information from agavi for the given action and module
     * name for the request method 'read'.
     *
     * @author Jan Schütze <jans@dracoblue.de>
     *
     * @param string $action name of action
     * @param string $module name of module
     *
     * @return array of parameters for all registered validators
     */
    protected function getParametersForActionAndModule($action, $module, $method = 'read')
    {
        /*
         * Agavi uses different coding standard, so we ignore the following...
         *
         * @codingStandardsIgnoreStart
         */
        $parameters = array();

        $this->getContext()->getController()->initializeModule($module);

        $validationManager = $this->getContext()->createInstanceFor('validation_manager');
        $validationConfig = \AgaviToolkit::evaluateModuleDirective($module, 'agavi.validate.path', array(
            'moduleName' => $module,
            'actionName' => \AgaviToolkit::canonicalName($action),
        ));

        if (is_readable($validationConfig)) {
            require(\AgaviConfigCache::checkConfig($validationConfig, $this->getContext()->getName()));
        }

        foreach ($validationManager->getChilds() as $validator) {
            $property = new \ReflectionProperty(get_class($validator), 'arguments');
            $property->setAccessible(true);
            $arguments = $property->getValue($validator);
            $parameters[] = array(
                'name' => implode(', ', $arguments),
                'class' => $validator->getParameter('class'),
                'required' => $validator->getParameter('required', 'true'),
                'description' => $validator->getParameter('description', null),
                'base' => $validator->getParameter('base', null)
            );
        }

        /*
         * @codingStandardsIgnoreEnd
         */

        return $parameters;
    }
}
