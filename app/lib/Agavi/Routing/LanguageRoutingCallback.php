<?php

namespace Honeybee\FrameworkBinding\Agavi\Routing;

use AgaviContext;
use AgaviRoutingCallback;
use AgaviException;
use AgaviExecutionContainer;

/**
 * The ProjectLanguageRoutingCallbacck response to locale information
 * matched inside a url and applies the corresponding settings to our agavi env.
 * It is also responseable for correctly providing i18n data for url generation.
 */
class LanguageRoutingCallback extends AgaviRoutingCallback
{
    /**
     * An array containing locales that are available to use.
     *
     * @var         array
     */
    protected $available_locales = array();

    protected $translation_manager;

    /**
     * Initialize this ProjectLanguageRoutingCallback instance.
     *
     * @param       AgaviContext $context
     *
     * @param       array $route
     */
    public function initialize(AgaviContext $context, array &$route)
    {
        parent::initialize($context, $route);

        // reduce method calls
        $this->translation_manager = $this->context->getTranslationManager();

        // store the available locales, that's faster
        $this->available_locales = $this->context->getTranslationManager()->getAvailableLocales();
    }

    /**
     * Routing callback that is invoked when the root we are applied to matches (routing runtime).
     *
     * @param       array $parameters
     * @param       AgaviExecutionContainer $container
     *
     * @return      boolean
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @codingStandardsIgnoreStart
     */
    public function onMatched(array &$parameters, AgaviExecutionContainer $container) // @codingStandardsIgnoreEnd
    {
        // let's check if the locale is allowed
        try {
            $this->context->getTranslationManager()->getLocaleIdentifier($parameters['locale']);
            // yup, worked. now lets set that as a cookie
            $this->context->getController()->getGlobalResponse()->setCookie(
                'locale',
                $parameters['locale'],
                '+1 month'
            );

            return true;
        } catch (AgaviException $e) {
            // uregistered or ambigious locale... uncool!
            // onNotMatched will be called for us next
            return false;
        }
    }

    /**
     * Routing callback that is invoked when the root we are applied to does not match (routing runtime).
     *
     * @param       array $parameters
     * @param       AgaviExecutionContainer $container
     *
     * @return      boolean
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @codingStandardsIgnoreStart
     */
    public function onNotMatched(AgaviExecutionContainer $container) // @codingStandardsIgnoreEnd
    {
        // the pattern didn't match, or onMatched() returned false.
        // that's sad. let's see if there's a locale set in a cookie from an earlier visit.
        $request_data = $this->context->getRequest()->getRequestData();

        $cookie = $request_data->getCookie('locale');

        if ($cookie !== null) {
            try {
                $this->translation_manager->setLocale($cookie);

                return;
            } catch (AgaviException $e) {
                // bad cookie :<
                $this->context->getController()->getGlobalResponse()->unsetCookie('locale');
            }
        }

        if ($request_data->hasHeader('Accept-Language')) {
            $has_intl = function_exists('locale_accept_from_http');
            // try to find the best match for the locale
            $locales = self::parseAcceptLanguage($request_data->getHeader('Accept-Language'));

            foreach ($locales as $locale) {
                try {
                    if ($has_intl) {
                        // we don't use this directly on Accept-Language,
                        // because we might not have the preferred locale,
                        // but another one in any case, it might help clean up the value a bit further
                        $locale = locale_accept_from_http($locale);
                    }

                    $this->translation_manager->setLocale($locale);

                    return;
                } catch (AgaviException $e) {
                    return;
                }
            }
        }
    }

    /**
     * Routing callback that is invoked when the root we are applied to does not match (routing runtime).
     *
     * @param       array $parameters
     * @param       AgaviExecutionContainer $container
     *
     * @return      boolean
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onGenerate(array $default_parameters, array &$user_parameters, array &$options)
    {
        if (isset($user_parameters['locale'])) {
            $user_parameters['locale'] = $this->getShortestLocaleIdentifier(
                $user_parameters['locale']
            );
        } else {
            $user_parameters['locale'] = $this->getShortestLocaleIdentifier(
                $this->translation_manager->getCurrentLocaleIdentifier()
            );
        }

        return true;
    }

    /**
     * Resolve a given locale identifier to its corresponding short identifier.
     *
     * @staticvar   string $locale_map
     *
     * @param       string $localeIdentifier
     *
     * @return      string
     */
    public function getShortestLocaleIdentifier($localeIdentifier)
    {
        static $locale_map = null;

        if ($locale_map === null) {
            foreach ($this->available_locales as $locale) {
                $locale_map[$locale['identifierData']['language']][] = $locale['identifierData']['territory'];
            }
        }

        if (count($locale_map[$short = substr($localeIdentifier, 0, 2)]) > 1) {
            return $localeIdentifier;
        } else {
            return $short;
        }
    }

    /**
     * Parses the value of a http accept-language header into an array of identifiers.
     *
     * @param       string $accept_language
     *
     * @return      array
     */
    protected static function parseAcceptLanguage($accept_language)
    {
        $locales = array();

        $match_count = preg_match_all(
            '/(^|\s*,\s*)([a-zA-Z]{1,8}(-[a-zA-Z]{1,8})*)\s*(;\s*q\s*=\s*(1(\.0{0,3})?|0(\.[0-9]{0,3})))?/i',
            $accept_language,
            $matches
        );

        if ($match_count) {
            foreach ($matches[2] as &$language) {
                $language = str_replace('-', '_', $language);
            }

            foreach ($matches[5] as &$quality) {
                if ($quality === '') {
                    $quality = '1';
                }
            }

            $locales = array_combine($matches[2], $matches[5]);
            arsort($locales, SORT_NUMERIC);
        }

        return array_keys($locales);
    }
}
