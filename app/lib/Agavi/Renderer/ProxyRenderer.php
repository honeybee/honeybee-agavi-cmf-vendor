<?php

namespace Honeygavi\Agavi\Renderer;

use AgaviException;
use AgaviRenderer;
use AgaviTemplateLayer;

/**
 * The AgaviProxyRenderer will try to load multiple renderers by name and
 * return the first successful attempt to render the given layer.
 *
 * This allows you to mix e.g. .twig and .php templates in the same directory,
 * but still use the proper renderer for each file.
 *
 * @example <pre>
 *  <!-- Add this to the output_types.xml -->
 *  <renderer name="proxy" class="AgaviProxyRenderer">
 *      <ae:parameter name="renderers">
 *          <ae:parameter>twig</ae:parameter>
 *          <ae:parameter>php</ae:parameter>
 *      </ae:parameter>
 *  </renderer>
 * </pre>
 */
class ProxyRenderer extends AgaviRenderer
{
    public function render(
        AgaviTemplateLayer $layer,
        array &$attributes = array(),
        array &$slots = array(),
        array &$moreAssigns = array()
    ) {
        if (!isset($moreAssigns['container'])) {
            throw new AgaviException('Cannot find container in moreAssigns.');
        }

        $container = $moreAssigns['container'];
        $output_type = $container->getOutputType();
        $layer_extension = $layer->getParameter('extension');
        $attempts = array();

        foreach ($this->getParameter('renderers') as $renderer_name) {
            /**
             * @var $renderer AgaviRenderer
             */
            $renderer = $output_type->getRenderer($renderer_name);

            try {
                /*
                 * We need to reconnect the layer to the renderer, since it relies on the
                 * renderer-property to get the the default extension.
                 */
                $layer->setRenderer($renderer);

                /*
                 * Setting the renderer is not enough, because we may have the extension set in a previous
                 * iteration. So we have to remove the parameter, if we want to rely on agavi's default
                 * behaviour.
                 */
                if ($layer_extension) {
                    $attempts[] = '"' . $renderer_name . '" with extension "' . $layer_extension . '"';
                    $layer->setParameter('extension', $layer_extension);
                } else {
                    $attempts[] = '"' . $renderer_name . '" with extension "' . $renderer->getDefaultExtension() . '"';
                    $layer->removeParameter('extension');
                }

                return $renderer->render($layer, $attributes, $slots, $moreAssigns);
            } catch (AgaviException $exception) {
                /*
                 * Ooops, it didn't work. Let's try the next one. BUT if we get a real agavi exception,
                 * throw it. See AgaviStreamTemplateLayer.class.php#L104
                 */
                if (strpos($exception->getMessage(), ' could not be found') < 0) {
                    throw $exception;
                }
            }
        }

        /*
         * No template found, time to throw an exception.
         */
        $explanation = "\n\nRenderers tried:\n - " . implode("\n - ", $attempts) .
                "\n\nUsually this happens when a template is not in one of the expected 'targets' locations:\n - " .
                implode("\n - ", $layer->getParameter('targets', []));

        throw new AgaviException(
            sprintf(
                'Rendering the template "%s" on the layer "%s" with renderer "%s" failed (module="%s").%s',
                $layer->getTemplate(),
                $layer->getName(),
                get_class($this),
                $layer->getParameter('module', ''),
                $explanation
            )
        );
    }
}
