<?php

namespace Honeygavi\Agavi\Filter;

use AgaviContext;
use AgaviFilterChain;
use AgaviExecutionContainer;
use AgaviFilter;
use AgaviIGlobalFilter;

/**
 * @TODO TO MAKE THIS FILTER WORK CORRECTLY THE AgaviWebRequest CLASS SHOULD BE
 * MADE CONFIGURABLE TO NOT ALWAYS CONVERT ALL UNMAPPED REQUEST METHODS TO GET.
 *
 * This filter checks if the application supports the incoming request method
 * and returns an appropriate HTTP "501 Not Implemented" if necessary.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html
 *
 * "An origin server SHOULD return […] 501 (Not Implemented) if the method is
 * unrecognized or not implemented by the origin server."
 */
class HttpStatus501Filter extends AgaviFilter implements AgaviIGlobalFilter
{
    /**
     * Execute the check and set a 501 response as next if necessary.
     *
     * @param AgaviFilterChain a FilterChain instance
     * @param AgaviExecutionContainer current execution container
     */
    public function execute(AgaviFilterChain $filter_chain, AgaviExecutionContainer $container)
    {
        if ($this->getParameter('debug')) {
            error_log(
                '[' . __METHOD__ . '] Incoming=' .
                $container->getModuleName() . '/' .
                $container->getActionName() . ' OT=' .
                $container->getOutputType()->getName() . ' METHOD=' .
                $container->getRequestMethod()
            );
        }

        $supported_methods = $this->getParameter('implemented_methods');
        $used_method = array_search(
            $container->getRequestMethod(),
            $this->getContext()->getRequest()->getParameter('method_names', array())
        );

        $method_implemented = in_array($used_method, $supported_methods, true);

        if (!$method_implemented) {
            $module = $this->getParameter('module');
            $action = $this->getParameter('action');
            $output_type = $this->getParameter('output_type');
            $method = $this->getParameter('method');

            $controller = $this->getContext()->getController();

            if (false === $controller->checkActionFile($module, $action)) {
                $error = 'Invalid "module"/"action" parameter combination supplied.';
                $error .= 'Action file does not exist for "%1$s"/"%2$s".';
                $error = sprintf($error, $module, $action);
                throw new \InvalidArgumentException($error);
            }
            $container->setModuleName($module);
            $container->setActionName($action);

            $output_type = $this->getParameter('output_type');
            if (!empty($output_type)) {
                $container->setOutputType($controller->getOutputType($output_type));
            }

            $method = $this->getParameter('method');
            if (!empty($method)) {
                $container->setRequestMethod($method);
            }

            $container->setParameter('redirect_to_501', true);
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

        $filter_chain->execute($container);
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
                // @todo get that list directly from the AgaviWebRequest "method_names"
                'implemented_methods' => array(
                    'GET',
                    'POST',
                    'PUT',
                    'PATCH',
                    'DELETE',
                    'HEAD',
                    'OPTIONS'
                ),
                'module' => 'Honeybee_Core',
                'action' => 'System/Error501',
                'output_type' => null,
                'request_method' => null,
                'debug' => false
            )
        );

        parent::initialize($context, $parameters);
    }
}
