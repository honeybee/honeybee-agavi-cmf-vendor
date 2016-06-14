<?php

namespace Honeybee\Tests\Unit\FrameworkBinding\Agavi\Validator;

use AgaviContext;
use AgaviUnitTestCase;
use Honeybee\FrameworkBinding\Agavi\Validator\AggregateRootTypeCommandValidator;

class AggregateRootTypeCommandValidatorTest extends AgaviUnitTestCase
{
    public function testSomething()
    {
        $vm = $this->getContext()->createInstanceFor('validation_manager');
        $vm->clear();
        $validator = $vm->createValidator(AggregateRootTypeCommandValidator::CLASS, []);

        $this->assertInstanceOf(AgaviContext::CLASS, $validator->getContext());
    }
}
