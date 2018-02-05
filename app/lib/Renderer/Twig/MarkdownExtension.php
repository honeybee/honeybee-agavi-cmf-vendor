<?php

namespace Honeygavi\Renderer\Twig;

use Michelf\Markdown;
use Twig_Extension;
use Twig_Function;

class MarkdownExtension extends Twig_Extension
{
    public function getFunctions()
    {
        return [
            new Twig_Function('markdown', function ($markdown_text) {
                return $this->markdown($markdown_text);
            }),
        ];
    }

    public function markdown($markdown_text)
    {
        return Markdown::defaultTransform($markdown_text);
    }

    public function getName()
    {
        return 'MarkdownConverter';
    }
}
