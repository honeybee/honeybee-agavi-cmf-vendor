<?php

namespace Honeygavi\Template\Twig\Extension;

use Honeybee\Common\Util\StringToolkit;
use Traversable;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Extension that adds some filters that may be useful.
 */
class ToolkitExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('cast_to_array', function ($value) {
                return $this->castToArray($value);
            }),
            new TwigFilter('as_studly_caps', function ($value) {
                return $this->asStudlyCaps($value);
            }),
            new TwigFilter('as_camel_case', function ($value) {
                return $this->asCamelCase($value);
            }),
            new TwigFilter('as_snake_case', function ($value) {
                return $this->asSnakeCase($value);
            }),
            new TwigFilter('format_bytes', function ($value) {
                return $this->formatBytes($value);
            }),
            new TwigFilter('shuffle', function ($array) {
                if ($array instanceof Traversable) {
                    $array = \iterator_to_array($array, false);
                }
                \shuffle($array);
                return $array;
            }),
            new TwigFilter(
                'truncate',
                 /**
                  * @author Henrik Bjornskov <hb@peytz.dk>
                  * @see https://github.com/twigphp/Twig-extensions/blob/master/src/TextExtension.php
                  */
                function (Environment $env, $value, $length = 30, $preserve = false, $separator = '...') {
                    if (\mb_strlen($value, $env->getCharset()) > $length) {
                        if ($preserve) {
                            // If breakpoint is on the last word, return the value without separator.
                            if (false === ($breakpoint = \mb_strpos($value, ' ', $length, $env->getCharset()))) {
                                return $value;
                            }

                            $length = $breakpoint;
                        }

                        return \rtrim(\mb_substr($value, 0, $length, $env->getCharset())).$separator;
                    }

                    return $value;
                }, [
                    'needs_environment' => true
                ]
            ),
            new TwigFilter(
                'wordwrap',
                /**
                 * @author Henrik Bjornskov <hb@peytz.dk>
                 * @see https://github.com/twigphp/Twig-extensions/blob/master/src/TextExtension.php
                 */
                function (Environment $env, $value, $length = 80, $separator = "\n", $preserve = false) {
                    $sentences = [];
                    $previous = \mb_regex_encoding();
                    \mb_regex_encoding($env->getCharset());
                    $pieces = \mb_split($separator, $value);
                    \mb_regex_encoding($previous);
                    foreach ($pieces as $piece) {
                        while (!$preserve && \mb_strlen($piece, $env->getCharset()) > $length) {
                            $sentences[] = \mb_substr($piece, 0, $length, $env->getCharset());
                            $piece = \mb_substr($piece, $length, 2048, $env->getCharset());
                        }
                        $sentences[] = $piece;
                    }
                    return \implode($separator, $sentences);
                }, [
                    'needs_environment' => true
                ]
            ),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('starts_with', function ($haystack, $needle) {
                return $this->startsWith($haystack, $needle);
            }),
            new TwigFunction('ends_with', function ($haystack, $needle) {
                return $this->endsWith($haystack, $needle);
            }),
            new TwigFunction('replace', function ($subject, $search, $replace, $count = null) {
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
        if ($count === null) {
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
        return self::CLASS;
    }
}
