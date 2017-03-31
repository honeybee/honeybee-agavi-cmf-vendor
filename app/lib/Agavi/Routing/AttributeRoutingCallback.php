<?php

namespace Honeygavi\Agavi\Routing;

use AgaviRoutingCallback;
use Trellis\Runtime\Attribute\AttributeInterface;
use InvalidArgumentException;

/**
 * This routing callback is used to generate a URL that contains the
 * attribute path when an AttributeInterface instance is given.
 */
class AttributeRoutingCallback extends AgaviRoutingCallback
{
    /**
     * Gets executed when the route of this callback is about to be reverse
     * generated into an URL. It converts a given Trellis AttributeInterface
     * instance to the attribute path that can be used in the generated URL.
     *
     * @param array $default_parameters default parameters stored in the route
     * @param array $user_parameters parameters the user supplied to AgaviRouting::gen()
     * @param array $user_options options the user supplied to AgaviRouting::gen()
     *
     * @return bool false when this route part should not be generated. True otherwise.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onGenerate(array $default_parameters, array &$user_parameters, array &$user_options)
    {
        if (!array_key_exists('attribute', $user_parameters)) {
            if ($this->getParameter('mandatory', true)) {
                throw new InvalidArgumentException(
                    'An "attribute" user parameter that implements AttributeInterface is expected for URL generation.'
                );
            }

            // attribute not present, but it's not mandatory => allow url generation
            return true;
        }

        // convert attribute parameter if necessary
        $attribute = $user_parameters['attribute']->getValue();
        if ($attribute instanceof AttributeInterface) {
            $ro = $this->getContext()->getRouting();
            $user_parameters['attribute'] = $ro->createValue($attribute->getPath());
            $user_parameters['module'] = $ro->createValue($attribute->getRootType()->getPrefix());
        }

        return true;
    }
}
