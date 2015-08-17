<?php

namespace Honeybee\FrameworkBinding\Agavi\Translation;

use AgaviTranslationManager;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Ui\TranslatorInterface;
use Psr\Log\LoggerInterface;

class AgaviTranslator implements TranslatorInterface
{
    protected $config;
    protected $tm;
    protected $logger;

    public function __construct(ConfigInterface $config, AgaviTranslationManager $tm, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->tm = $tm;
        $this->logger = $logger;
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
        $translation = $this->tm->_($message, $domain, $locale, $params);

        if ($this->config->get('log_missing_translations', false)) {
            if ($translation === $message) {
                $params_as_string = var_export($params, true);
                if ($params_as_string === 'NULL') {
                    $params_as_string = '';
                }
                $this->logger->info(sprintf('[%s] [%s] [%s] [%s]', $locale, $domain, $message, $params_as_string));
            }
        }

        if ($this->config->get('use_fallback', false)) {
            if ($translation === $message && !is_null($fallback)) {
                $translation = $fallback;
            }
        }

        return $translation;
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
        $translation = $this->tm->__($message_singular, $message_plural, $amount, $domain, $locale, $params);

        if ($this->config->get('use_fallback', false)) {
            $translation_not_found = $translation === $message_singular || $translation === $message_plural;
            if ($translation_not_found) {
                if ($amount > 1 && !is_null($fallback_plural)) {
                    $translation = $fallback_plural;
                } elseif (!is_null($fallback_singular)) {
                    $translation = $fallback_singular;
                }
            }
        }

        return $translation;
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
        return $this->tm->_d($date, $domain, $locale);
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
        return $this->tm->_c($currency, $domain, $locale);
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
        return $this->tm->_n($number, $domain, $locale);
    }
}

