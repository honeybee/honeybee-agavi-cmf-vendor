<?php

namespace Honeygavi\Renderer\Twig;

use AgaviConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to have AgaviConfig methods available as simple
 * and short functions in twig templates. This should save some keystrokes.
 *
 * In addition to that there are some filters that may be useful.
 */
class HoneybeeToolkitExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('ac', function ($setting_name, $default_value = null) {
                return $this->ac($setting_name, $default_value);
            }),
        ];
    }

    /**
     * Returns the value for the given AgaviConfig setting key.
     *
     * @param string $setting_name key of setting to return
     * @param mixed $default_value value to return of key is not found
     *
     * @return mixed string of setting value or null if key not exists or array in case of nested parameters
     */
    public function ac($setting_name, $default_value = null)
    {
        return AgaviConfig::get($setting_name, $default_value);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string extension name.
     */
    public function getName()
    {
        return \get_class($this);
    }
}
