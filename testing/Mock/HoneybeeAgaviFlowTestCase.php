<?php

namespace Honeybee\Tests\Mock;

use AgaviFlowTestCase;
use Mockery;
use ReflectionObject;
use Symfony\Component\DomCrawler\Crawler;
use Text_Template;

class HoneybeeAgaviFlowTestCase extends AgaviFlowTestCase
{
    protected $crawler;

    protected $fixture_data = [];

    const UUID_REGEX = '\w{8}\-\w{4}\-\w{4}\-\w{4}\-\w{12}';

    protected function getResponse()
    {
        return $this->response;
    }

    protected function getFixtureData()
    {
        return $this->fixture_data;
    }

    protected function getCrawler()
    {
        if (!$this->crawler) {
            $this->crawler = new Crawler($this->getResponse()->getContent());
        }

        return $this->crawler;
    }

    protected function getElement($selector)
    {
        return $this->getCrawler()->filter($selector);
    }

    protected function assertTagExists($selector)
    {
        $this->assertNotEmpty($this->getElement($selector));
    }

    protected function assertTagContains($selector, $value)
    {
        $this->assertContains($value, $this->getElement($selector)->text());
    }

    protected function assertTagCount($selector, $size)
    {
        $this->assertEquals($size, $this->getElement($selector)->count());
    }

    protected function assertIsoDate($value)
    {
        $this->assertInstanceOf(\DateTime::CLASS, new \DateTime($value));
    }

    protected function prepareTemplate(Text_Template $template)
    {
        parent::prepareTemplate($template);

        // Assuming flow tests are always run with preserveGlobalState = true
        $reflected = new ReflectionObject($template);
        $property = $reflected->getProperty('values');
        $property->setAccessible(true);
        $oldVars = $property->getValue($template);

        $server_globals = sprintf('
	        $_SERVER["REQUEST_URI"] = %s;
		    $_SERVER["SCRIPT_NAME"] = %s;
			',
            var_export($this->getDispatchScriptName() . $this->getRoutingInput(), true),
            var_export($this->getDispatchScriptName(), true)
        );

        // add server to isolated bootstrap because routing is initialized before tests ):
        $template->setVar([
            'globals' => $oldVars['globals'] . PHP_EOL . $server_globals
        ]);

        // force swift mailer autoloader to run (if service is not delegated)? ):
        if (isset($oldVars['constants'])) {
            $constantsWithoutSwiftInit = array_filter(
                explode(PHP_EOL, $oldVars['constants']),
                function ($line) { return strpos($line, 'SWIFT_INIT_LOADED') === false; }
            );
            $template->setVar([
                'constants' => implode(PHP_EOL, $constantsWithoutSwiftInit)
            ]);
        }
    }

    public function tearDown()
    {
        $this->dynamic_data = [];
        $this->crawler = null;
        Mockery::close();
    }
}