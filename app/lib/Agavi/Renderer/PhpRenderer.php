<?php

namespace Honeybee\FrameworkBinding\Agavi\Renderer;

use AgaviPhpRenderer;
use AgaviTemplateLayer;

/**
 * Adds some more_assigns and methods that should be available in php templates.
 */
class PhpRenderer extends AgaviPhpRenderer
{
    protected $template_name;

    /**
     * Returns the given value htmlspecialchars escaped.
     *
     * @param string $value string to escape.
     *
     * @return string htmlspecialchars escaped string
     */
    public function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render the presentation and return the result.
     *
     * @param AgaviTemplateLayer $layer template layer to render.
     * @param array $attributes template variables that are available.
     * @param array $slots slots that area available.
     * @param array $more_assigns additional global assigns.
     *
     * @return string rendered result.
     */
    public function render(
        AgaviTemplateLayer $layer,
        array &$attributes = array(),
        array &$slots = array(),
        array &$more_assigns = array()
    ) {
        $this->template_name = $layer->getParameter('template');

        $allowed_variables = array(
            'container',
            'validation_manager',
            'view'
        );

        foreach ($more_assigns as $name => $value) {
            if (in_array($name, $allowed_variables)) {
                $this->$name = $value;
            }
        }

        return parent::render($layer, $attributes, $slots, $more_assigns);
    }
}
