<?php

namespace Honeygavi\Routing;

use AgaviRoutingCallback;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\EntityInterface;
use Honeygavi\Logging\LogTrait;

/**
 * This routing callback is used to generate a URL that contains
 * the resource identifier in some place (and maybe other parts
 * like a title slug or similar)
 */
class OptionalResourceRoutingCallback extends AgaviRoutingCallback
{
    use LogTrait;

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
        if (\array_key_exists('resource', $user_parameters) && is_object($user_parameters['resource'])) {
            $resource = $user_parameters['resource']->getValue();
            if ($resource instanceof EntityInterface) {
                $root_entity = $resource->getRoot() ?: $resource;
                $ro = $this->getContext()->getRouting();
                $user_parameters['resource'] = $ro->createValue($root_entity->getIdentifier());
                $user_parameters['module'] = $ro->createValue(
                    \sprintf(
                        '%s-%s-%s',
                        StringToolkit::asSnakeCase($root_entity->getType()->getVendor()),
                        StringToolkit::asSnakeCase($root_entity->getType()->getPackage()),
                        StringToolkit::asSnakeCase($root_entity->getType()->getName())
                    )
                );
            } else {
                $this->logDebug(
                    "Skipping invalid resource parameter. Expected instance of ",
                    EntityInterface::CLASS
                );
            }
        }

        return true;
    }
}
