<?php

namespace Honeygavi\Renderer\Twig;

use Michelf\Markdown;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MarkdownExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('markdown', function ($markdown_text) {
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
