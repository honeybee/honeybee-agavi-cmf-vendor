<?php
namespace Honeygavi\Renderer\Twig;

use AgaviContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to have AgaviTranslationManager methods available as simple
 * and short functions in twig templates. This should save some keystrokes.
 */
class TranslationManagerExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('_', function (
                $message,
                $domain = null,
                $locale = null,
                array $parameters = null,
                $defaultTranslation = null
            ) {
                return $this->translate($message, $domain, $locale, $parameters, $defaultTranslation);
            }),
            new TwigFunction('_c', function ($number, $domain = null, $locale = null) {
                return $this->translateCurrency($number, $domain, $locale);
            }),
            new TwigFunction('_n', function ($number, $domain = null, $locale = null) {
                return $this->translateNumber($number, $domain, $locale);
            }),
            new TwigFunction('_d', function ($date, $domain = null, $locale = null) {
                return $this->translateDate($date, $domain, $locale);
            }),
            new TwigFunction('__', function (
                $singularMessage,
                $pluralMessage,
                $amount,
                $domain = null,
                $locale = null,
                array $parameters = null,
                $defaultSingularTranslation = null,
                $defaultPluralTranslation = null
            ) {
                return $this->translatePlural(
                    $singularMessage,
                    $pluralMessage,
                    $amount,
                    $domain,
                    $locale,
                    $parameters,
                    $defaultSingularTranslation,
                    $defaultPluralTranslation
                );
            }),
        ];
    }

    /**
     * Translate a message into the current locale.
     *
     * @param mixed $message message to be translated.
     * @param string $domain domain in which the translation should be done.
     * @param AgaviLocale $locale locale which should be used for formatting.
     * @param array $parameters parameters which should be used for sprintf on the translated string.
     *
     * @return string translated message.
     */
    public function translate(
        $message,
        $domain = null,
        $locale = null,
        array $parameters = null,
        $defaultTranslation = null
    ) {
        $tm = AgaviContext::getInstance()->getTranslationManager();
        $translation = $tm->_($message, $domain, $locale, $parameters);
        if ($translation === $message && !is_null($defaultTranslation)) {
            $translation = $defaultTranslation;
        }
        return $translation;
    }

    /**
     * Translate a singular/plural message into the current locale.
     *
     * @param string $singularMessage message for the singular form.
     * @param string $pluralMessage message for the plural form.
     * @param int $amount amount for which the translation should be done.
     * @param string $domain domain in which the translation should be done.
     * @param \AgaviLocale $locale locale which should be used for formatting.
     * @param array $parameters parameters which should be used for sprintf on the translated string.
     *
     * @return string translated message.
     */
    public function translatePlural(
        $singularMessage,
        $pluralMessage,
        $amount,
        $domain = null,
        $locale = null,
        array $parameters = null,
        $defaultSingularTranslation = null,
        $defaultPluralTranslation = null
    ) {
        $tm = AgaviContext::getInstance()->getTranslationManager();
        $translation = $tm->__($singularMessage, $pluralMessage, $amount, $domain, $locale, $parameters);
        $translation_not_found = $translation === $singularMessage || $translation === $pluralMessage;
        if ($translation_not_found) {
            if ($amount > 1 && !is_null($defaultPluralTranslation)) {
                $translation = $defaultPluralTranslation;
            } elseif (!is_null($defaultSingularTranslation)) {
                $translation = $defaultSingularTranslation;
            }
        }

        return $translation;
    }

    /**
     * Formats a date in the current locale.
     *
     * @param mixed $date date to be formatted.
     * @param string $domain domain in which the date should be formatted.
     * @param \AgaviLocale $locale locale which should be used for formatting.
     *
     * @return string formatted date.
     */
    public function translateDate($date, $domain = null, $locale = null)
    {
        $tm = AgaviContext::getInstance()->getTranslationManager();
        return $tm->_d($date, $domain, $locale);
    }

    /**
     * Formats a currency amount in the current locale.
     *
     * @param mixed $number number to be formatted.
     * @param string $domain domain in which the amount should be formatted.
     * @param \AgaviLocale $locale locale which should be used for formatting.
     *
     * @return string formatted number.
     */
    public function translateCurrency($number, $domain = null, $locale = null)
    {
        $tm = AgaviContext::getInstance()->getTranslationManager();
        return $tm->_c($number, $domain, $locale);
    }

    /**
     * Formats a number in the current locale.
     *
     * @param mixed $number number to be formatted.
     * @param string $domain domain in which the number should be formatted.
     * @param \AgaviLocale $locale locale which should be used for formatting.
     *
     * @return string formatted number.
     */
    public function translateNumber($number, $domain = null, $locale = null)
    {
        $tm = AgaviContext::getInstance()->getTranslationManager();
        return $tm->_n($number, $domain, $locale);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string extension name.
     */
    public function getName()
    {
        return 'TranslationManager';
    }
}
