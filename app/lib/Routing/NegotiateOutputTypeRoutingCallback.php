<?php

namespace Honeygavi\Routing;

use AgaviContext;
use AgaviExecutionContainer;
use AgaviRoutingCallback;
use AgaviWebRequest;

/**
 * Inspects the Accept HTTP header and sets the output_type according to
 * the 'acceptable_media_types' parameter of output types. That parameter
 * defines whether an output type feels responsible for that incoming media
 * type. When a media type has priority in the Accept header the output type
 * that feels responsible for that media type is set.
 * The order of output types in the output_types xml file is important for
 * equally prioritized Accept header values!
 */
class NegotiateOutputTypeRoutingCallback extends AgaviRoutingCallback
{
    /**
     * @var Honeygavi\Controller\HoneybeeAgaviController
     */
    protected $controller = null;

    /**
     * @param AgaviContext $context
     * @param array $route
     */
    public function initialize(AgaviContext $context, array &$route)
    {
        parent::initialize($context, $route);

        $this->controller = $this->context->getController();
    }

    /**
     * Gets executed when the route of this callback matched.
     *
     * @param array The parameters generated by this route.
     * @param AgaviExecutionContainer The original execution container.
     *
     * @return bool false as routes with this callback should never match.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onMatched(array &$parameters, AgaviExecutionContainer $container)
    {
        $request = $this->context->getRequest();
        if (!$request instanceof AgaviWebRequest) {
            return true;
        }

        $request_data = $request->getRequestData();

        // allow the route to match even though the Accept header is missing
        // we don't set a default output type, as the application has one
        if (!$request_data->hasHeader('Accept')) {
            return true;
        }

        // Example value: text/html;q=0.91,application/xhtml+xml;q=0.92,application/xml;q=0.9,*/*;q=0.8
        // ,application/json;odata=fullmetadata;q=0.98,application/vnd.amundsen.collection+json,foo/bar
        $accept_header_value = $request_data->getHeader('Accept');

        // all known/configured output types
        $all_output_types = $this->controller->getOutputTypes();

        // all output types that define acceptable media types
        $supported_output_types = array();
        foreach ($all_output_types as $output_type) {
            $media_types = $output_type->getParameter('acceptable_media_types', array());
            if (\is_string($media_types)) {
                $media_types = array($media_types);
            }
            if (!empty($media_types)) {
                $supported_output_types[$output_type->getName()] = $media_types;
            }
        }

        // get a prioritized list of all media types (over all output types)
        $all_supported_media_types = array();
        foreach ($supported_output_types as $output_type_name => $media_types) {
            foreach ($media_types as $media_type) {
                $all_supported_media_types[$media_type] = $output_type_name;
            }
        }

        $acceptable_media_types_for_client = self::parseAcceptString($accept_header_value);

        // get the first media type that is acceptable for the server as well in order of the client's prio
        $matching_output_type = null;
        foreach ($acceptable_media_types_for_client as $acceptable_media_type) {
            if (isset($all_supported_media_types[$acceptable_media_type])) {
                $matching_output_type = $all_supported_media_types[$acceptable_media_type];
                break;
            }
        }

        // set the current output type to the matching one and return early
        if ($matching_output_type !== null) {
            $new_output_type = $this->controller->getOutputType($matching_output_type);
            $container->setOutputType($new_output_type);
            return true;
        }

        // media types did not match, but someone configured a default output type to be used
        if ($matching_output_type === null && !empty($this->getParameter('default_output_type'))) {
            $default_output_type = $this->controller->getOutputType($this->getParameter('default_output_type'));
            $container->setOutputType($default_output_type);
            return true;
        }

         // no matching output type was found for the given Accept header
         // and no default output type was defined => return a 406 http error

        $response_content = <<<EOC
# 406 Not Acceptable

The Accept header of the request does not specify a media type that
is currently acceptable. The following media types are supported instead:


EOC;

        foreach ($all_supported_media_types as $media_type => $output_format) {
            $response_content .= '- ' . $media_type . PHP_EOL;
        }

        $response = $this->getContext()->createInstanceFor('response');
        $response->setContent($response_content);
        $response->setContentType('text/x-markdown; charset=UTF-8');
        $response->setHttpStatusCode('406');

        return $response;
    }

    /**
     * Gets executed when the route of this callback did not match.
     *
     * @param AgaviExecutionContainer The original execution container.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onNotMatched(AgaviExecutionContainer $container)
    {
        return;
    }

    /**
     * Gets executed when the route of this callback is about to be reverse
     * generated into an URL.
     *
     * @param array The default parameters stored in the route.
     * @param array The parameters the user supplied to AgaviRouting::gen().
     * @param array The options the user supplied to AgaviRouting::gen().
     *
     * @return bool false as this route part should not be generated.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onGenerate(array $default_parameters, array &$user_parameters, array &$user_options)
    {
        return false;
    }

    /**
     * Parses the given accept header and returns a list of all media types sorted
     * descending by their q value while the more specific types are preferred over
     * types/subtypes with asterisks.
     *
     * @return array media type strings (sorted descending by their q value)
     */
    public static function parseAcceptString($accept_header_value)
    {
        if (!\is_string($accept_header_value)) {
            return [];
        }

        $weighted_types = [];

        $regex = <<<EOR
#
(
    ^|
    \s*,\s*
)
(
    (
        \*/\*|
        [^/]+/\*|
        [^/]+/[^;,$]+
    )
    (
        \s*;\s*
        (?!\s*q\s*=)[^=]+=[^;,$]+
    )*
)
\s*
(
    ;\s*q\s*=\s*(1(\.0{0,3})?|0(\.[0-9]{0,3}))
)?
#ix
EOR;

        // trim leading/trailing commas and whitespace
        $accept_header_value = \trim($accept_header_value, " \t\n\r\0\x0B,");

        if (\preg_match_all($regex, $accept_header_value, $matches)) {
            foreach ($matches[6] as &$quality) {
                if ($quality === '') {
                    $quality = '1';
                }
            }

            $types = [];
            $types = \array_combine($matches[2], $matches[6]);

            // adjust scores of "type/*" or "*/*" media types as those should
            // weigh less than the more specific types in our later matching
            $adjusted = [];
            foreach ($types as $type => $q) {
                $cnt = \substr_count($type, '*');
                if ($cnt > 0) {
                    $adjusted[$type] = ($q !== 1) ? (int)$q - $cnt : -$cnt;
                } else {
                    $adjusted[$type] = $q;
                }
            }

            // sort without running into reordering issues that arsort() maybe has
            $temp = [];
            $i = 0;
            foreach ($adjusted as $type => $q) {
                $temp[] = [$i++, $type, $q];
            }

            \uasort($temp, function($a, $b) {
                return $a[2] == $b[2] ? ($a[0] > $b[0]) : ($a[2] < $b[2] ? 1 : -1);
            });

            foreach ($temp as $val) {
                $weighted_types[$val[1]] = $val[2];
            }
        }

        return \array_keys($weighted_types);
    }
}
