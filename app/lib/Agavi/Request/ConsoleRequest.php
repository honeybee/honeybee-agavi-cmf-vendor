<?php

namespace Honeybee\FrameworkBinding\Agavi\Request;

use AgaviConsoleRequest;
use Honeybee\FrameworkBinding\Agavi\Request\HoneybeeUploadedFile;
use Honeybee\FrameworkBinding\Agavi\Request\ConsoleRequestDataHolder;

class ConsoleRequest extends AgaviConsoleRequest
{
    public function __construct()
    {
        parent::__construct();

        $this->setParameters([
            'request_data_holder_class' => ConsoleRequestDataHolder::CLASS,
            'uploaded_file_class' => HoneybeeUploadedFile::CLASS,
        ]);
    }
}
