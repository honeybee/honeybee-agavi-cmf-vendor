<?php

namespace Honeygavi\Template\Twig\Extension;

use Honeybee\Common\Util\StringToolkit;
use Twig_Extension;
use Twig_Filter;
use Twig_Function;

/**
 * Extension that adds some filters that may be useful.
 */
class ToolkitExtension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            new Twig_Filter('cast_to_array', function ($value) {
                return $this->castToArray($value);
            }),
            new Twig_Filter('as_studly_caps', function ($value) {
                return $this->asStudlyCaps($value);
            }),
            new Twig_Filter('as_camel_case', function ($value) {
                return $this->asCamelCase($value);
            }),
            new Twig_Filter('as_snake_case', function ($value) {
                return $this->asSnakeCase($value);
            }),
            new Twig_Filter('format_bytes', function ($value) {
                return $this->formatBytes($value);
            }),
        );
    }

    public function getFunctions()
    {
        return [
            new Twig_Function('starts_with', function ($haystack, $needle) {
                return $this->startsWith($haystack, $needle);
            }),
            new Twig_Function('ends_with', function ($haystack, $needle) {
                return $this->endsWith($haystack, $needle);
            }),
            new Twig_Function('replace', function ($subject, $search, $replace, $count = null) {
                return $this->replace($subject, $search, $replace, $count);
            }),
        ];
    }

    public function castToArray($value)
    {
        return (array)$value;
    }

    public function asStudlyCaps($value)
    {
        return StringToolkit::asStudlyCaps($value);
    }

    public function asCamelCase($value)
    {
        return StringToolkit::asCamelCase($value);
    }

    public function asSnakeCase($value)
    {
        return StringToolkit::asSnakeCase($value);
    }

    public function formatBytes($value)
    {
        return StringToolkit::formatBytes($value);
    }

    public function startsWith($haystack, $needle)
    {
        return StringToolkit::startsWith($haystack, $needle);
    }

    public function endsWith($haystack, $needle)
    {
        return StringToolkit::endsWith($haystack, $needle);
    }

    public function replace($subject, $search, $replace, $count = null)
    {
        if (is_null($count)) {
            return str_replace($search, $replace, $subject);
        }

        return str_replace($search, $replace, $subject, $count);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string extension name.
     */
    public function getName()
    {
        return ToolkitExtension::CLASS;
    }
}
