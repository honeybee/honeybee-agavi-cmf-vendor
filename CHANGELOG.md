# Changelog

- ``Zend\Permissions\**`` is now ``Laminas\Permissions\**`` as we like changes that are important. Code has been adjusted. Projects shouldn't run into problems as there's a bridge package with class aliases.
- Twig related code now uses the namespaced variant. Twig v1 is no longer supported. Twig v3 is allowed to be used. Projects must check compatibility.
- The https://github.com/twigphp/Twig-extensions repository is abandoned "in favor of Twig Core Extra extensions", whatever that means. There are some replacements suggested. The `shuffle` filter has been moved to the ToolkitExtension. The `time_diff` filter is no longer present and projects must provide it themselves from now on. The IntlExtension has replacements in twig v3 that are no longer present in twig v2. The TextExtension is missing as well and the `u` filter from twig v3 is suggested as replacement - the `truncate` and `wordwrap` filters are now in the ToolkitExtension for the time being. It's unnecessarily complicated.
- added [Twig Intl Extension](https://github.com/twigphp/intl-extra) with filters like ``format_date`` etc.
- bumped deps
