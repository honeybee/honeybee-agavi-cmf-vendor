<?php

namespace Honeybee\Tests\Unit\FrameworkBinding\Agavi\Validator;

use AgaviContext;
use AgaviUnitTestCase;
use Honeybee\FrameworkBinding\Agavi\Validator\TrellisTargetValidator;

class TrellisTargetValidatorTest extends AgaviUnitTestCase
{
	public function testExecute()
	{
		$vm = $this->getContext()->createInstanceFor('validation_manager');
		$vm->clear();
		$validator = $vm->createValidator(TrellisTargetValidator::CLASS, []);

	    $this->assertInstanceOf(AgaviContext::CLASS, $validator->getContext());
    }
}