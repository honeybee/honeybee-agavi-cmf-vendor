<?php

namespace Honeybee\Tests\Flow\Honeybee\SystemAccount\User\Create;

use AgaviWebResponse;
use Honeybee\Tests\HoneybeeAgaviFlowTestCase;

class CreateActionFlowTest extends HoneybeeAgaviFlowTestCase
{
    /**
     * @agaviRequestMethod read
     * @agaviRoutingInput /en_US/honeybee-system_account-user/create
     */
    public function testCreateInput()
    {
        $this->dispatch();
        $this->assertInstanceOf(AgaviWebResponse::CLASS, $this->getResponse());
        $this->assertEquals('200', $this->getResponse()->getHttpStatusCode());
    }
}
