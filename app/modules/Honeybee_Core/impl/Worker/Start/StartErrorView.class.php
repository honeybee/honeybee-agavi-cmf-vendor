<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Worker_Start_StartErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        echo join(PHP_EOL, $this->getErrorMessages()) . PHP_EOL;
    }
}
