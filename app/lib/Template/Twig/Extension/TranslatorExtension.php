<?php

namespace Honeygavi\Template\Twig\Extension;

use Honeygavi\Ui\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Extension that wraps the TranslatorInterface methods to make them available in twig templates.
 */
class TranslatorExtension extends AbstractExtension
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('translate', function (
                $message,
                $domain = null,
                $locale = null,
                array $params = null,
                $fallback = null
            ) {
                return $this->translate($message, $domain, $locale, $params, $fallback);
            }),
            new TwigFilter('translateCurrency', function ($currency, $domain = null, $locale = null) {
                return $this->translateCurrency($currency, $domain, $locale);
            }),
            new TwigFilter('translateNumber', function ($number, $domain = null, $locale = null) {
                return $this->translateNumber($number, $domain, $locale);
            }),
            new TwigFilter('translateDate', function ($date, $domain = null, $locale = null) {
                return $this->translateDate($date, $domain, $locale);
            }),
            new TwigFilter('translatePlural', function (
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
            new TwigFunction('_', function (
                $message,
                $domain = null,
                $locale = null,
                array $params = null,
                $fallback = null
            ) {
                return $this->translate($message, $domain, $locale, $params, $fallback);
            }),
            new TwigFunction('_c', function ($currency, $domain = null, $locale = null) {
                return $this->translateCurrency($currency, $domain, $locale);
            }),
            new TwigFunction('_n', function ($number, $domain = null, $locale = null) {
                return $this->translateNumber($number, $domain, $locale);
            }),
            new TwigFunction('_d', function ($date, $domain = null, $locale = null) {
                return $this->translateDate($date, $domain, $locale);
            }),
            new TwigFunction('__', function (
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
