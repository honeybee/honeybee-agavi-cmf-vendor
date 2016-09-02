<?php

namespace Honeybee\Tests\Unit\FrameworkBinding\Agavi\Routing;

use AgaviContext;
use AgaviWebResponse;
use Honeybee\FrameworkBinding\Agavi\Routing\NegotiateOutputTypeRoutingCallback;
use Honeybee\Tests\Mock\HoneybeeAgaviUnitTestCase;

class NegotiateOutputTypeRoutingCallbackTest extends HoneybeeAgaviUnitTestCase
{
    public function setUp()
    {
        $_SERVER['HTTP_ACCEPT'] = 'omgomgomg';
    }

    public function testUnknownAcceptHeaderValueLeadsTo406Response()
    {
        $routes = [];
        $rcb = new NegotiateOutputTypeRoutingCallback();
        $rcb->initialize(AgaviContext::getInstance(null), $routes);
        $container = $this->getContext()->createInstanceFor('execution_container');

        $result = $rcb->onMatched($routes, $container);

        $this->assertInstanceOf(AgaviWebResponse::CLASS, $result);

        $this->assertEquals('406', $result->getHttpStatusCode());
    }

    /**
     * @dataProvider acceptHeaderMatches
     */
    public function testAcceptHeaderParsingSucceeds($value, $expected)
    {
        $this->assertSame($expected, NegotiateOutputTypeRoutingCallback::parseAcceptString($value));
    }

    public function acceptHeaderMatches()
    {
        return [
            [ '', [] ],
            [ null, [] ],
            [ [], [] ],
            [ new \stdClass, [] ],
            [ 'text/html', [ 'text/html' ] ],
            [ 'text/html, */*;q=0.1', [ 'text/html', '*/*' ] ],
            [ 'text/html, */*; q=0.1 ', [ 'text/html', '*/*' ] ],
            [
                'application/hal+json, application/json, */*; q = 0.01, audio/*; q=0.2',
                [
                    'application/hal+json',
                    'application/json',
                    'audio/*',
                    '*/*',
                ]
            ],
            [
                'application/hal+json, application/json, */*;q=0.8, audio/*; q=0.2',
                [
                    'application/hal+json',
                    'application/json',
                    'audio/*',
                    '*/*',
                ]
            ],
            [
                'text/html;q=0.91,application/xhtml+xml;q=0.92,application/xml;q=0.9,*/*;q=0.8,' .
                'application/json;odata=fullmetadata;q=0.98,application/vnd.amundsen.collection+json,foo/bar',
                [
                    'application/vnd.amundsen.collection+json',
                    'foo/bar',
                    'application/json;odata=fullmetadata',
                    'application/xhtml+xml',
                    'text/html',
                    'application/xml',
                    '*/*',
                ]
            ],
            [
                'text/html;q=0.91,application/xhtml+xml;q=0.92,application/xml;q=0.9,*/*;q=0.8,' .
                'application/json;odata=fullmetadata,application/vnd.amundsen.collection+json,foo/bar',
                [
                    'application/json;odata=fullmetadata',
                    'application/vnd.amundsen.collection+json',
                    'foo/bar',
                    'application/xhtml+xml',
                    'text/html',
                    'application/xml',
                    '*/*',
                ]
            ],
            [
                'text/plain; q=0.5, text/html, text/x-dvi; q=0.8, text/x-c',
                [
                    'text/html',
                    'text/x-c',
                    'text/x-dvi',
                    'text/plain',
                ]
            ],
            [
                'text/*, text/html, text/html;level=1, */*',
                [
                    'text/html',
                    'text/html;level=1',
                    'text/*',
                    '*/*',
                ]
            ],
            [
                'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5',
                [
                    'text/html;level=1',
                    'text/html',
                    'text/html;level=2',
                    'text/*',
                    '*/*',
                ]
            ],
            [
                'text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, text/*;q=0.3, */*;q=0.5',
                [
                    'text/html;level=1',
                    'text/html',
                    'text/html;level=2',
                    'text/*',
                    '*/*',
                ]
            ],
            [
                'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                [
                    'text/html',
                    'application/xhtml+xml',
                    'image/webp',
                    'application/xml',
                    '*/*',
                ]
            ],
            [
                'text/html;q=a , */*;q=0.8',
                [
                    'text/html',
                    '*/*',
                ]
            ],
            [
                'text/html;a=b;q=a;c=d,*/* q=0.8',
                [
                    'text/html;a=b',
                    '*/*',
                ]
            ],
            [
                ',c/d,*/* q=0.8',
                [
                    'c/d',
                    '*/*',
                ]
            ],
        ];
    }
}
