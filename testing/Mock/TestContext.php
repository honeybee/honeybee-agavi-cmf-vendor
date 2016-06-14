<?php

namespace Honeybee\Tests\Mock;

use Auryn\Injector as DiContainer;
use Auryn\StandardReflector;
use Honeybee\FrameworkBinding\Agavi\Context;

class TestContext extends Context
{
    public function getServiceLocator()
    {
        if (!$this->service_locator) {
            $di_container = new DiContainer(new StandardReflector);
            $di_container->share($di_container);

            $service_provisioner = $di_container->make(TestServiceProvisioner::CLASS);
            // @todo support overriding provisioners
            $this->service_locator = $service_provisioner->provision();
        }

        return $this->service_locator;
    }
}
