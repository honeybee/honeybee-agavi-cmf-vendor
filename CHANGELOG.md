# Changelog

- updated `honeybee/trellis`, `shrink0r/workflux`, `honeybee/honeybee`, `honeybee/agavi` and `shrink0r/monatic` libs prior this update to check for obvious twig and php 7.4 upgrade problems *fingersCrossed*
- ``Zend\Permissions\**`` is now ``Laminas\Permissions\**`` as we like changes that are important. Code has been adjusted. Projects shouldn't run into problems as there's a bridge package with class aliases.
- twig: twig related code now uses the namespaced variant. Twig v1 is no longer supported. Twig v3 is allowed to be used. Projects must check compatibility.
- twig: the https://github.com/twigphp/Twig-extensions repository is abandoned "in favor of Twig Core Extra extensions", whatever that means. There are some replacements suggested. The `shuffle` filter has been moved to the ToolkitExtension. The `time_diff` filter is no longer present and projects must provide it themselves from now on. The IntlExtension has replacements in twig v3 that are no longer present in twig v2. The TextExtension is missing as well and the `u` filter from twig v3 is suggested as replacement - the `truncate` and `wordwrap` filters are now in the ToolkitExtension for the time being. It's unnecessarily complicated.
- twig: added [Twig Intl Extension](https://github.com/twigphp/intl-extra) with filters like ``format_date`` etc.
- twig: fixed usage of `spaceless` filter by using `apply spaceless`
- twig: HtHaml libs is still included, but there's now a functionally equivalent twig extension in the cmf as the lib's extension doesn't use namespaced twig
- bumped deps to allow symfony 5.x components
    - adjusted code to work with Process component changes
- made user MailService password reset email twig template identifier configurable (use ``email_template`` on the service definition)
