<?php

namespace Honeybee\Tests\Unit\FrameworkBinding\Agavi\Validator;

use AgaviContext;
use Honeygavi\Validator\TrellisTargetValidator;
use Honeybee\Tests\Mock\HoneybeeAgaviUnitTestCase;

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
