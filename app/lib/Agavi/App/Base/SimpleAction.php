<?php

namespace Honeygavi\Agavi\App\Base;

use AgaviRequestDataHolder;

/**
 * The Base\SimpleAction serves as the base action to all simple actions
 * implemented inside of honeybee.
 */
abstract class SimpleAction extends Action
{
    public function execute(AgaviRequestDataHolder $request_data)
    {
        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSimple()
    {
        return true;
    }
}
