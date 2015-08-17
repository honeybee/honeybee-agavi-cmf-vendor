<?php

namespace Honeybee\FrameworkBinding\Agavi\Request;

use AgaviWebRequest;
use Honeybee\FrameworkBinding\Agavi\Request\WebRequestDataHolder;

class WebRequest extends AgaviWebRequest
{
    public function __construct()
    {
        parent::__construct();

        $this->setParameters([
            'request_data_holder_class' => WebRequestDataHolder::CLASS
        ]);
    }
}
