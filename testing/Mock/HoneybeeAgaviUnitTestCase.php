<?php

namespace Honeybee\Tests\Mock;

use AgaviUnitTestCase;
use Mockery;

class HoneybeeAgaviUnitTestCase extends AgaviUnitTestCase
{
    public function tearDown()
    {
        Mockery::close();
    }
}