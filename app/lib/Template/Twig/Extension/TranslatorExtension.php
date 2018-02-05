<?php

namespace Honeygavi\Template\Twig\Extension;

use Honeygavi\Ui\TranslatorInterface;
use Twig_Extension;
use Twig_Filter;
use Twig_Function;

/**
 * Extension that wraps the TranslatorInterface methods to make them available in twig templates.
 */
class TranslatorExtension extends Twig_Extension
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new Twig_Filter('translate', function (
                $message,
                $domain = null,
                $locale = null,
                array $params = null,
                $fallback = null
            ) {
                return $this->translate($message, $domain, $locale, $params, $fallback);
            }),
            new Twig_Filter('translateCurrency', function ($currency, $domain = null, $locale = null) {
                return $this->translateCurrency($currency, $domain, $locale);
            }),
            new Twig_Filter('translateNumber', function ($number, $domain = null, $locale = null) {
                return $this->translateNumber($number, $domain, $locale);
            }),
            new Twig_Filter('translateDate', function ($date, $domain = null, $locale = null) {
                return $this->translateDate($date, $domain, $locale);
            }),
            new Twig_Filter('translatePlural', function (
                $message_singular,
                $message_plural,
                $amount,
                $domain = null,
                $locale = null,
                array $params = null,
                $fallback_singular = null,
                $fallback_plural = null
            ) {
                return $this->translatePlural(
                    $message_singular,
                    $message_plural,
                    $amount,
                    $domain,
                    $locale,
                    $params,
                    $fallback_singular,
                    $fallback_plural
                );
            }),
        ];
    }

    public function getFunctions()
    {
        return [
            new Twig_Function('_', function (
                $message,
                $domain = null,
                $locale = null,
                array $params = null,
                $fallback = null
            ) {
                return $this->translate($message, $domain, $locale, $params, $fallback);
            }),
            new Twig_Function('_c', function ($currency, $domain = null, $locale = null) {
                return $this->translateCurrency($currency, $domain, $locale);
            }),
            new Twig_Function('_n', function ($number, $domain = null, $locale = null) {
                return $this->translateNumber($number, $domain, $locale);
            }),
            new Twig_Function('_d', function ($date, $domain = null, $locale = null) {
                return $this->translateDate($date, $domain, $locale);
            }),
            new Twig_Function('__', function (
                $message_singular,
                $message_plural,
                $amount,
                $domain = null,
                $locale = null,
                array $params = null,
                $fallback_singular = null,
                $fallback_plural = null
            ) {
                return $this->translatePlural(
                    $message_singular,
                    $message_plural,
                    $amount,
                    $domain,
                    $locale,
                    $params,
                    $fallback_singular,
                    $fallback_plural
                );
            }),
        ];
    }

    /**
     * Translate a message into the current or given locale.
     *
     * @param string $message message or message identifier to be translated
     * @param string $domain domain to use for translation
     * @param string $locale identifier of the locale to translate into
     * @param array $params parameters to use for translation
     * @param string $fallback text to use when translation fails
     *
     * @return string translated message
     */
    public function translate($message, $domain = null, $locale = null, array $params = null, $fallback = null)
    {
        return $this->translator->translate($message, $domain, $locale, $params, $fallback);
    }

    /**
     * Translate a singular/plural message into the current or given locale.
     *
     * @param string $message_singular message or message identifier for the singular form
     * @param string $message_plural message or message identifier for the plural form
     * @param int $amount amount to use for translation
     * @param string $domain domain to use for translation
     * @param string $locale identifier of the locale to translate into
     * @param array $params parameters to use for translation
     * @param string $fallback_singular text to use when translation fails
     * @param string $fallback_plural text to use when translation fails
     *
     * @return string translated message
     */
    public function translatePlural(
        $message_singular,
        $message_plural,
        $amount,
        $domain = null,
        $locale = null,
        array $params = null,
        $fallback_singular = null,
        $fallback_plural = null
    ) {
        return $this->translator->translatePlural(
            $message_singular,
            $message_plural,
            $amount,
            $domain,
            $locale,
            $params,
            $fallback_singular,
            $fallback_plural
        );
    }

    /**
     * Formats a date/datetime in the current or given locale.
     *
     * @param mixed $date date or datetime to be formatted
     * @param string $domain domain to use for translation
     * @param string $locale identifier of the locale to use for formatting
     *
     * @return string formatted date
     */
    public function translateDate($date, $domain = null, $locale = null)
    {
        return $this->translator->translateDate($date, $domain, $locale);
    }

    /**
     * Formats a currency amount in the current or given locale.
     *
     * @param mixed $currency currency to be formatted
     * @param string $domain domain to use for translation
     * @param string $locale identifier of the locale to use for formatting
     *
     * @return string formatted currency
     */
    public function translateCurrency($currency, $domain = null, $locale = null)
    {
        return $this->translator->translateCurrency($currency, $domain, $locale);
    }

    /**
     * Formats a number in the current locale.
     *
     * @param mixed $number number to be formatted
     * @param string $domain domain to use for translation
     * @param string $locale identifier of the locale to use for formatting
     *
     * @return string formatted number
     */
    public function translateNumber($number, $domain = null, $locale = null)
    {
        return $this->translator->translateNumber($number, $domain, $locale);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string extension name.
     */
    public function getName()
    {
        return static::CLASS;
    }
}
