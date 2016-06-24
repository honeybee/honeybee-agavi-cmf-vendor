<?php

namespace Honeybee\Tests\Mock;

use AgaviUnitTestCase;
use Mockery;
use ReflectionObject;
use Text_Template;

class HoneybeeAgaviUnitTestCase extends AgaviUnitTestCase
{
    protected function prepareTemplate(Text_Template $template)
    {
        parent::prepareTemplate($template);

        // Assuming tests are always run with preserveGlobalState = true
        $reflected = new ReflectionObject($template);
        $property = $reflected->getProperty('values');
        $property->setAccessible(true);
        $oldVars = $property->getValue($template);

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
        Mockery::close();
    }
}