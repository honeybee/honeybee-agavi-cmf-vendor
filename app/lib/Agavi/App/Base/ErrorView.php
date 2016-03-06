<?php

namespace Honeybee\FrameworkBinding\Agavi\App\Base;

use AgaviRequestDataHolder;
use AgaviWebResponse;
use Exception;

/**
 * The CoreErrorBaseView serves as the base view to all error views implemented
 * inside of the Core module to make it easier to support a multitude of output
 * types in all of those views.
 */
class ErrorView extends View
{
    const DEFAULT_ERROR_TITLE = '400 Bad Request';
    const DEFAULT_ERROR_MESSAGE = 'There was an error handling the request.';
    const DEFAULT_ERROR_HTTP_STATUS_CODE = 400;

    /**
     * Handle errors for the html output type by displaying a html template
     * and returning it as a response with appropriate http status code.
     *
     * @param AgaviRequestDataHolder $request_data
     */
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();
        $this->setupHtml($request_data);
        $this->setHttpStatusCode();
    }

    /**
     * Handle errors for the json output type by returning a simple json
     * string with appropriate http status code.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string json response with information message
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeJson(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();
        $this->setHttpStatusCode();

        return json_encode(
            array(
                'logref' => $this->getAttribute('_logref'),
                'title' => $this->getAttribute('_title'),
                'message' => $this->getAttribute('_message')
            ),
            JSON_FORCE_OBJECT
        );
    }

    /**
     * Handle errors for the application/vnd.error+json output type by
     * returning a simple json string with appropriate http status code.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string json response with information message
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeVnderrorjson(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();
        $this->setHttpStatusCode();

        return json_encode(
            array(
                'logref' => $this->getAttribute('_logref'),
                'title' => $this->getAttribute('_title'),
                'message' => $this->getAttribute('_message')
            ),
            JSON_FORCE_OBJECT
        );
    }

    /**
     * Handle errors for commandline interfaces using an exit code of 1 for the
     * shell.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeConsole(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();

        $error_message = '';

        $plain_text_message = $this->getPlainTextErrorMessage();

        if (!empty($plain_text_message)) {
            $error_message .= $plain_text_message;
        }

        $error_message .= 'Usage: bin/cli <routename> [parameters]' . PHP_EOL;

        return $this->cliError($error_message);
    }

    /**
     * Handle errors for the binary output type by returning a response with
     * an appropriate http status code.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string response with information message
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeBinary(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();
        $this->setHttpStatusCode();
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent($this->getPlainTextErrorMessage());
    }

    /**
     * Handle errors for the pdf output type by returning a response with http
     * status code.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string response with information message
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executePdf(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();
        $this->setHttpStatusCode();
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent($this->getPlainTextErrorMessage());
    }

    /**
     * Handle errors for the xml output type by returning a simple xml string
     * with http status code.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string XML content with information
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeXml(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();
        $this->setHttpStatusCode();
        return '<?xml version="1.0" encoding="UTF-8"?>' .
            '<resource><title>' . $this->getAttribute('_title') . '</title>' .
            '<message>' . $this->getAttribute('_message') . '</message>' .
            '</resource>';
    }

    /**
     * Handle errors for the atomxml output type by returning a simple xml
     * string with http status code.
     *
     * @param AgaviRequestDataHolder $request_data
     *
     * @return string XML content with 405 information
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeAtomxml(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->logMatchedRoute();
        $this->setDefaultAttributes();
        $this->setHttpStatusCode();
        return '<?xml version="1.0" encoding="UTF-8"?>' .
            '<feed xmlns="http://www.w3.org/2005/Atom">' .
            '<title>' . $this->getAttribute('_title') . '</title>' .
            '</feed>';
    }

    /**
     * Logs matched routes routing information to all debug logs for easier
     * debugging of errors.
     */
    protected function logMatchedRoute()
    {
        $this->findRelatedAction();
        $requested = array();
        foreach (array('_module', '_action') as $name) {
            if ($this->getAttribute($name)) {
                $requested[] = $this->getAttribute($name);
            }
        }
        $origin = empty($requested) ? '' : ' - Requested module/action: ' . join('/', $requested);

        $output_type = $this->getResponse()->getOutputType()->getName();
        $request_method = $this->request->getMethod();

        $uri = '';
        if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
            $uri = 'for request URI "' . $_SERVER['REQUEST_URI'] . '"';
        } else {
            $uri = 'for input: ' . $this->routing->getInput();
        }

        $log_message = '';
        $route_names_array = $this->request->getAttribute('matched_routes', 'org.agavi.routing');
        if (!empty($route_names_array)) {
            $main_route = $this->routing->getRoute(reset($route_names_array));
            $main_module = $main_route['opt']['module'];
            $main_action = $main_route['opt']['action'];
            $log_message = sprintf(
                'module="%s" action="%s" outputType="%s" requestMethod="%s" matchedUri="%s" - matchedRoutes: "%s" %s',
                $main_module,
                $main_action,
                $output_type,
                $request_method,
                $uri,
                implode(', ', $route_names_array),
                $origin
            );
        } else {
            $log_message = sprintf(
                'No route matched (requestMethod "%s", outputType "%s") matchedUri="%s" %s',
                $request_method,
                $output_type,
                $uri,
                $origin
            );
        }

        $this->logDebug($log_message);
    }

    /**
     * Stores requested module and action from system forwards in attributes to
     * use them in templates and logging.
     *
     * @author Tom Anheyer <tom.anheyer@berlinonline.de>
     *
     * @return void
     *
     * @see AgaviExecutionContainer::createSystemActionForwardContainer()
     */
    protected function findRelatedAction()
    {
        if ($this->hasAttribute('_action')) {
            return;
        }

        // @see AgaviExecutionContainer::createSystemActionForwardContainer()
        $container =  $this->getContainer();
        foreach (array('error_404', 'module_disabled', 'secure', 'login', 'unavailable') as $type) {
            $namespace = 'org.agavi.controller.forwards.' . $type;
            if ($container->hasAttributeNamespace($namespace)) {
                $this->setAttribute('_module', $container->getAttribute('requested_module', $namespace));
                $this->setAttribute('_action', $container->getAttribute('requested_action', $namespace));
                $exception = $container->getAttribute('_exception', $namespace);
                if ($exception instanceof Exception) {
                    $this->setAttribute('_exception', $exception);
                }
                break;
            }
        }
    }

    protected function getPlainTextErrorMessage()
    {
        $error_message = '';

        $title = $this->getAttribute('_title');
        $message = $this->getAttribute('_message');
        $logref = $this->getAttribute('_logref');

        if (!empty($message) || !empty($title)) {
            $error_message .= PHP_EOL . 'Details about the error:' . PHP_EOL;
        }

        if (!empty($logref)) {
            $error_message .= '- Logref: ' . $logref . PHP_EOL;
        }

        if (!empty($title)) {
            $error_message .= '- Title: ' . $title . PHP_EOL;
        }

        if (!empty($message)) {
            $error_message .= '- Message: ' . $message . PHP_EOL;
        }

        // @todo add links to error information and description?
        // @see https://github.com/blongden/vnd.error

        if (!empty($message) || !empty($title)) {
            $error_message .= PHP_EOL;
        }

        return $error_message;
    }

    protected function setHttpStatusCode()
    {
        $response = $this->getResponse();

        if ($response instanceof AgaviWebResponse) {
            $response->setHttpStatusCode($this->getHttpStatusCodeFromContainer());
        }
    }

    protected function setDefaultAttributes()
    {
        $this->setAttribute('_title', $this->getTitleFromContainer());
        $this->setAttribute('_message', $this->getMessageFromContainer());
        $this->setAttribute('_http_status_code', $this->getHttpStatusCodeFromContainer());
        $this->setAttribute('_logref', $this->getLogrefFromContainer());
    }

    protected function getTitleFromContainer()
    {
        return $this->getContainer()->getAttribute('title', 'org.honeybee.error', $this->getTitle());
    }

    protected function getMessageFromContainer()
    {
        return $this->getContainer()->getAttribute('message', 'org.honeybee.error', $this->getMessage());
    }

    protected function getHttpStatusCodeFromContainer()
    {
        return $this->getContainer()->getAttribute(
            'http_status_code',
            'org.honeybee.error',
            $this->getHttpStatusCode()
        );
    }

    protected function getLogrefFromContainer()
    {
        return $this->getContainer()->getAttribute('logref', 'org.honeybee.error', $this->getLogref());
    }

    protected function getTitle()
    {
        return self::DEFAULT_ERROR_TITLE;
    }

    protected function getMessage()
    {
        return self::DEFAULT_ERROR_MESSAGE;
    }

    protected function getHttpStatusCode()
    {
        return self::DEFAULT_ERROR_HTTP_STATUS_CODE;
    }

    protected function getLogref()
    {
        return 'error';
    }
}
