<?php

namespace Honeybee\Tests\Flow\Honeybee\SystemAccount\User\Create;

use AgaviWebResponse;
use Honeybee\Tests\Mock\HoneybeeAgaviFlowTestCase;

class CreateActionFlowTest extends HoneybeeAgaviFlowTestCase
{
    /**
     * @agaviRequestMethod read
     * @agaviRoutingInput /en_US/honeybee-system_account-user/create
     */
    public function testExecuteRead()
    {
        $this->dispatch();

        $this->assertInstanceOf(AgaviWebResponse::CLASS, $this->getResponse());
        $this->assertEquals('200', $this->getResponse()->getHttpStatusCode());
        $this->assertTagExists('form[action="http://testing.honeybee.com/index.php/en_US/honeybee-system_account-user/collection"]');
    }
}
