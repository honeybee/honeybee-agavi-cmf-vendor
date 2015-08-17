<?php

namespace Honeybee\FrameworkBinding\Agavi\Filter;

use AgaviContext;
use AgaviExecutionContainer;
use AgaviFilter;
use AgaviFilterChain;
use AgaviIGlobalFilter;
use Honeybee\FrameworkBinding\Agavi\Logging\Logger;
use InvalidArgumentException;

/**
 * This filter checks if the action supports the current request's method and
 * returns an appropriate HTTP "405 Method Not Allowed" if necessary.
 *
 * This filter DOES NOT support case-sensitive request method names as the http
 * request method names are mapped to case-insensitive php methods (d'oh).
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html
 *
 * "The method is case-sensitive. […] The list of methods allowed by a resource
 * can be specified in an Allow header field. The return code of the response
 * always notifies the client whether a method is currently allowed on a
 * resource, since the set of allowed methods can change dynamically. An origin
 * server SHOULD return the status code 405 (Method Not Allowed) if the method
 * is known by the origin server but not allowed for the requested resource"
 */
class HttpStatus405Filter extends AgaviFilter implements AgaviIGlobalFilter
{
    /**
     * Execute the checks and set a 405 response if necessary.
     *
     * @param AgaviFilterChain a FilterChain instance
     * @param AgaviExecutionContainer current execution container
     */
    public function execute(AgaviFilterChain $filter_chain, AgaviExecutionContainer $container)
    {
        $method_supported = true;

        // only handle non-slot actions (when in action filter context)
        $check_for_405 = !$container->getParameter('is_slot') && !$container->getParameter('redirect_to_501', false);
        if ($check_for_405) {
            if ($this->getParameter('debug')) {
                error_log(
                    '[' . __METHOD__ . '] Incoming=' .
                    $container->getModuleName() . '/' .
                    $container->getActionName() . ' OT=' .
                    $container->getOutputType()->getName() . ' METHOD=' .
                    $container->getRequestMethod()
                );
            }

            $controller = $this->getContext()->getController();

            $action_instance = $container->getActionInstance();
            if (!$this->getParameter('action_filter')) {
                // in a global filter the action instance is not yet initialized
                $action_instance = $controller->createActionInstance(
                    $container->getModuleName(),
                    $container->getActionName()
                );
            }

            $supported_methods = $this->getAllowedAgaviMethods($action_instance);

            if ($this->getParameter('debug')) {
                error_log(
                    '[' . __METHOD__ . '] ' .
                    $container->getModuleName() . '/' .
                    $container->getActionName() . ' supported_methods=' .
                    implode(',', $supported_methods) . ' request_method=' .
                    $container->getRequestMethod()
                );
            }

            // @todo what about actions that have isSimple=true?
            $method_supported = empty($supported_methods) || in_array(
                $container->getRequestMethod(),
                $supported_methods,
                true
            );

            // in case there is no execute*() method or no matching one
            // redirect the container to a 405 action/view
            if (!$method_supported) {
                $module = $this->getParameter('module');
                $action = $this->getParameter('action');
                $output_type = $this->getParameter('output_type');
                $method = $this->getParameter('method');

                if (!$controller->actionExists($module, $action)) {
                    $error = 'Invalid "module"/"action" parameter combination supplied.';
                    $error .= 'Action file does not exist for "%1$s"/"%2$s".';
                    $error = sprintf($error, $module, $action);
                    throw new InvalidArgumentException($error);
                }

                if ($this->getParameter('action_filter')) {
                    $next = $container->createExecutionContainer(
                        $module,
                        $action,
                        null,
                        empty($output_type) ? null : $output_type,
                        empty($method) ? null : $method
                    );
                    $container->setNext($next);
                    return false;
                } else { // global scope in global_filters.xml
                    $container->setModuleName($module);
                    $container->setActionName($action);

                    if (!empty($output_type)) {
                        $container->setOutputType($controller->getOutputType($output_type));
                    }

                    if (!empty($method)) {
                        $container->setRequestMethod($method);
                    }
                }
            }

            if ($this->getParameter('debug')) {
                error_log(
                    '[' . __METHOD__ . '] Outgoing=' .
                    $container->getModuleName() . '/' .
                    $container->getActionName() . ' OT=' .
                    $container->getOutputType()->getName() . ' METHOD=' .
                    $container->getRequestMethod()
                );
            }
        }

        $filter_chain->execute($container); // go on

        if ($check_for_405) {
            if (!$container->getResponse()->isContentMutable()) {
                return false;
            }

            if ($this->getParameter('debug')) {
                error_log(
                    '[' . __METHOD__ . '] Adding "Allow" header for=' .
                    $container->getModuleName() . '/' .
                    $container->getActionName() . ' OT=' .
                    $container->getOutputType()->getName() . ' METHOD=' .
                    $container->getRequestMethod()
                );
            }

            $add_allow_header = $this->context->getRequest()->getAttribute(
                'add_allow_header',
                'org.honeybee.HttpStatus405Filter'
            );

            if (!$method_supported || $add_allow_header) {
                $supported_methods = $this->getAllowedHttpMethods($action_instance);
                $container->getResponse()->setHttpHeader(
                    'Allow',
                    empty($supported_methods) ? 'GET' : implode(', ', $supported_methods)
                );
            }
        }
    }

    /**
     * Initialize this filter with default parameters.
     *
     * @param AgaviContext current application context
     * @param array associative array of initialization parameters
     */
    public function initialize(AgaviContext $context, array $parameters = array())
    {
        $this->setParameters(
            array(
                'module' => 'Honeybee_Core',
                'action' => 'System/Error405',
                'output_type' => null,
                'request_method' => null,
                'add_allow_header' => true,
                'debug' => false,
                'action_filter' => false
            )
        );

        parent::initialize($context, $parameters);

        $this->context->getRequest()->setAttributes($this->getParameters(), 'org.honeybee.HttpStatus405Filter');
    }

    /**
     * @return array of http request methods the action seems to implement
     */
    protected function getAllowedHttpMethods($action_instance)
    {
        $http_method_names_mapping = $this->context->getRequest()->getParameter('method_names', array());

        $allowed_http_methods = array_map(
            function ($method) use ($http_method_names_mapping) {
                if (preg_match('#^execute([A-Z][A-Za-z0-9_]+)#', $method, $matches)) {
                    $action_method_name = mb_strtolower($matches[1]); // read|write|foobar
                    $key = array_search($action_method_name, $http_method_names_mapping);
                    return $key ? $key : mb_strtoupper($action_method_name); // GET, POST, FOOBAR
                } else {
                    return false; // non-execute methods are not of interest
                }
            },
            (array) get_class_methods($action_instance)
        );

        return array_filter($allowed_http_methods); // skip 'false' entries and return 'read', 'write' etc.
    }

    /**
     * @return array of http request methods the action seems to implement
     */
    protected function getAllowedAgaviMethods($action_instance)
    {
        $http_method_names_mapping = $this->context->getRequest()->getParameter('method_names', array());

        $allowed_http_methods = array_map(
            function ($method) use ($http_method_names_mapping) {
                if (preg_match('#^execute([A-Z][A-Za-z0-9_]+)#', $method, $matches)) {
                    $action_method_name = mb_strtolower($matches[1]); // read|write|foobar
                    return $action_method_name;
                } else {
                    return false; // non-execute methods are not of interest
                }
            },
            (array) get_class_methods($action_instance)
        );

        return array_filter($allowed_http_methods); // skip 'false' entries and return 'read', 'write' etc.
    }
}
