<?php

namespace Honeygavi\Agavi\Logging;

use AgaviConfig;
use AgaviContext;
use AgaviLoggerMessage;
use Honeygavi\Agavi\Logging\FileLoggerAppender;

/**
 * Extends the FileLoggerAppender message with various system, agavi and
 * application information that may be helpful with debugging API calls.
 */
class ApiLoggerAppender extends FileLoggerAppender
{
    public function initialize(AgaviContext $context, array $parameters = array())
    {
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
        $server_name .= isset($_SERVER['SERVER_PORT']) ? ':' . $_SERVER['SERVER_PORT'] : '';

        $server_addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        $server_addr .= isset($_SERVER['SERVER_PORT']) ? ':' . $_SERVER['SERVER_PORT'] : '';

        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $remote_addr .= isset($_SERVER['REMOTE_PORT']) ? ':' . $_SERVER['REMOTE_PORT'] : '';

        $this->info = [
            'REMOTE_ADDR' => $remote_addr,
            'REQUEST_URI' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'REQUEST_METHOD' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            'CONTENT_TYPE' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '',
            'CONTENT_LENGTH' => isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : '',
            'X_FORWARDED_FOR' => isset($_SERVER['X_FORWARDED_FOR']) ? $_SERVER['X_FORWARDED_FOR'] : '',
            'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'HTTP_ACCEPT' => isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '',
            'HTTP_ACCEPT_LANGUAGE' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '',
            'HTTP_ACCEPT_ENCODING' => isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '',
            'HTTP_COOKIE' => isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : '',
            'HTTP_CONNECTION' => isset($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : '',
            'HTTP_CACHE_CONTROL' => isset($_SERVER['HTTP_CACHE_CONTROL']) ? $_SERVER['HTTP_CACHE_CONTROL'] : '',
            'HTTP_USER_AGENT' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'HTTP_REFERER' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'AGAVI_CONTEXT' => $context->getName(),
            'AGAVI_ENVIRONMENT' => AgaviConfig::get('core.environment'),
            'PHP_VERSION' => phpversion(),
        ];

        parent::initialize($context, $parameters);
    }

    /**
     * @param AgaviLoggerMessage $message
     *
     * @return void
     */
    public function write(AgaviLoggerMessage $message)
    {
        $message_text = $message->getMessage();

        $matched_module_and_action = '';
        $matched_routes = '';
        $route_names_array = $this->context->getRequest()->getAttribute('matched_routes', 'org.agavi.routing');
        if (!empty($route_names_array)) {
            $main_route = $this->context->getRouting()->getRoute(reset($route_names_array));
            $matched_module_and_action = $main_route['opt']['module'] . '/' . $main_route['opt']['action'];
            $matched_routes = implode(', ', $route_names_array);
        }

        $info = $this->info;

        $info['matched_module_and_action'] = $matched_module_and_action;
        $info['matched_routes'] = $matched_routes;

        $more_info = '';
        foreach ($info as $key => $value) {
            $more_info .= ' ' . $key . '=' . $value;
        }

        $message->setMessage($message_text . $more_info);

        parent::write($message);
    }
}
