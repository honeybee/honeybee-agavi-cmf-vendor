<?php

namespace Honeygavi\Tests\Unit\Validator;

use AgaviContext;
use Honeygavi\Validator\TrellisTargetValidator;
use Honeygavi\Tests\Mock\HoneybeeAgaviUnitTestCase;

class TrellisTargetValidatorTest extends HoneybeeAgaviUnitTestCase
{
    public function testExecute()
    {
        $vm = $this->getContext()->createInstanceFor('validation_manager');
        $vm->clear();
        $validator = $vm->createValidator(TrellisTargetValidator::CLASS, []);

        $this->assertInstanceOf(AgaviContext::CLASS, $validator->getContext());
    }
}
