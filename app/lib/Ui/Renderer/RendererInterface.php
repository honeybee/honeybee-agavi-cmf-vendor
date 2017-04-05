<?php

namespace Honeygavi\Ui\Renderer;

interface RendererInterface
{
    public function render($payload, $settings = null);
}
