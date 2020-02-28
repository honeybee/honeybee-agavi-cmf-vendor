<?php

namespace Honeygavi\Template\Twig\Extension;

use MtHaml\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Extension that wraps the UrlGeneratorInterface methods to make them available in twig templates.
 */
class MtHamlExtension extends AbstractExtension
{
    private $mthaml;

    public function __construct(Environment $mthaml = null)
    {
        $this->mthaml = $mthaml;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('mthaml_attributes', 'MtHaml\Runtime::renderAttributes'),
            new TwigFunction('mthaml_attribute_interpolation', 'MtHaml\Runtime\AttributeInterpolation::create'),
            new TwigFunction('mthaml_attribute_list', 'MtHaml\Runtime\AttributeList::create'),
            new TwigFunction('mthaml_object_ref_class', 'MtHaml\Runtime::renderObjectRefClass'),
            new TwigFunction('mthaml_object_ref_id', 'MtHaml\Runtime::renderObjectRefId'),
        ];
    }

    public function getFilters()
    {
        if (null === $this->mthaml) {
            return [];
        }

        return [
            new TwigFilter('mthaml_*', [$this, 'filter'], ['needs_context' => true, 'is_safe' => ['html']]),
        ];
    }

    public function filter(array $context, $name, $content)
    {
        return $this->mthaml->getFilter($name)->filter($content, $context, $this->mthaml->getOptions());
    }

    public function getName()
    {
        return 'MtHamlExtension';
    }
}
