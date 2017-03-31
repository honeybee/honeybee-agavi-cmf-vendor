<?php

namespace Honeygavi\Ui\OutputFormat;

interface OutputFormatInterface
{
    public function getName();
    public function getRendererLocator();

    public function getContentType();
    public function getAcceptableContentTypes();

    public function getMediaTypeInfo();
}
