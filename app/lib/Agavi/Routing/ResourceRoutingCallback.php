<?php

namespace Honeybee\FrameworkBinding\Agavi\Routing;

use AgaviRoutingCallback;
use Honeybee\EntityInterface;
use Honeybee\Common\Util\StringToolkit;
use InvalidArgumentException;

/**
 * This routing callback is used to generate a URL that contains
 * the resource identifier in some place (and maybe other parts
 * like a title slug or similar)
 */
class ResourceRoutingCallback extends AgaviRoutingCallback
{
    /**
     * Gets executed when the route of this callback is about to be reverse
     * generated into an URL. It converts a given Honeybee EntityInterface
     * instance to an identifier that can be used in the generated URL.
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
        if (!array_key_exists('resource', $user_parameters)) {
            throw new InvalidArgumentException(
                'A "resource" user parameter is expected for URL generation that implements: ' . EntityInterface::CLASS
            );
        }

        $resource = $user_parameters['resource']->getValue();

        if ($resource instanceof EntityInterface) {
            $ro = $this->getContext()->getRouting();
            $root_entity = $resource->getRoot() ?: $resource;
            $user_parameters['resource'] = $ro->createValue($root_entity->getIdentifier());
            /*
             * @see ModuleRoutingCallback that runs later on and would get only
             * the resource identifier instead of the ProjectionInterface instance
             *
             * @todo check for EntityTypeInterface on the resource and if it has a type at all?
             */
            $user_parameters['module'] = $ro->createValue(
                sprintf(
                    '%s-%s-%s',
                    strtolower($root_entity->getType()->getVendor()),
                    StringToolkit::asSnakeCase($root_entity->getType()->getPackage()),
                    StringToolkit::asSnakeCase($root_entity->getType()->getName())
                )
            );
        }

        return true;
    }
}
