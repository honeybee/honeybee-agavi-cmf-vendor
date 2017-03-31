<?php

namespace Honeygavi\Agavi\ConfigHandler;

use Honeybee\Common\Util\ArrayToolkit;
use AgaviXmlConfigDomElement;
use AgaviXmlConfigHandler;
use AgaviToolkit;

abstract class BaseConfigHandler extends AgaviXmlConfigHandler
{
    const NODE_SETTINGS = 'settings';

    const NODE_SETTING = 'setting';

    const ATTRIBUTE_NAME = 'name';

    /**
     * Parses the given 'settings' XML element into an associative
     * nested array of settings.
     *
     * @param AgaviXmlConfigDomElement $settings_parent
     *
     * @return array associative nested array with settings
     */
    protected function parseSettings(AgaviXmlConfigDomElement $settings_parent)
    {
        $settings = array();

        if (!$settings_parent->hasChild(self::NODE_SETTING) && $settings_parent->hasChild(self::NODE_SETTINGS)) {
            $settings_parent = $settings_parent->getChild(self::NODE_SETTINGS);
        }

        foreach ($settings_parent->getChildren(self::NODE_SETTING) as $setting_element) {
            $index =
                $setting_element->hasAttribute(self::ATTRIBUTE_NAME)
                ? trim($setting_element->getAttribute(self::ATTRIBUTE_NAME))
                : count(array_values($settings));

            if ($setting_element->hasChild(self::NODE_SETTINGS)) {
                $settings[$index] = $this->parseSettings(
                    $setting_element->getChild(self::NODE_SETTINGS)
                );
            } else {
                $value = $this->parseSettings($setting_element);
                if (is_array($value) && count($value)) {
                    $settings[$index] = $value;
                } else {
                    $settings[$index] = $this->parseValue($setting_element->getValue());
                }
            }
        }

        return $settings;
    }

    /**
     * Merges the given second array over the first one similar to the PHP internal
     * array_merge_recursive method, but does not change scalar values into arrays
     * when duplicate keys occur.
     *
     * @param array $first first or default array
     * @param array $second array to merge over the first array
     *
     * @return array merged result with scalar values still being scalar
     */
    public static function mergeSettings(array &$first, array &$second)
    {
        return ArrayToolkit::mergeScalarSafe($first, $second);
    }

    /**
     * Parses the given value XML element
     *
     * @param mixed $value
     *
     * @return mixes Parsed literalized and expanded value
     */
    protected function parseValue($value)
    {
        return AgaviToolkit::literalize(
            AgaviToolkit::expandDirectives(
                trim($value)
            )
        );
    }
}
