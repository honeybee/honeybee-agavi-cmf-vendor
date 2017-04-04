<?php

namespace Honeygavi\Routing;

use AgaviRoutingCallback;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Entity\EntityInterface;
use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Logging\LogTrait;
use InvalidArgumentException;

/**
 * Sets the module to use when the route is about to be reverse generated.
 * Uses given *Interface instances to set a module parameter.
 */
class ModuleRoutingCallback extends AgaviRoutingCallback
{
    use LogTrait;

    /**
     * Gets executed when the route of this callback is about to be reverse
     * generated into an URL.
     *
     * @param array The default parameters stored in the route.
     * @param array The parameters the user supplied to AgaviRouting::gen().
     * @param array The options the user supplied to AgaviRouting::gen().
     *
     * @return bool false as this route part should not be generated.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onGenerate(array $default_parameters, array &$user_parameters, array &$user_options)
    {
        $ro = $this->getContext()->getRouting();

        if (array_key_exists('attribute', $user_parameters)) {
            $attribute = $user_parameters['attribute']->getValue();
            if ($attribute instanceof AttributeInterface) {
                $user_parameters['module'] = $ro->createValue(
                    sprintf(
                        '%s-%s-%s',
                        StringToolkit::asSnakeCase($attribute->getRootType()->getVendor()),
                        StringToolkit::asSnakeCase($attribute->getRootType()->getPackage()),
                        StringToolkit::asSnakeCase($attribute->getRootType()->getName())
                    )
                );
                return true;
            }
        }

        if (array_key_exists('resource', $user_parameters)) {
            $resource = $user_parameters['resource']->getValue();
            if ($resource instanceof EntityInterface) {
                $root_entity = $resource->getRoot() ?: $resource;
                $ro = $this->getContext()->getRouting();
                $user_parameters['module'] = $ro->createValue(
                    sprintf(
                        '%s-%s-%s',
                        StringToolkit::asSnakeCase($root_entity->getType()->getVendor()),
                        StringToolkit::asSnakeCase($root_entity->getType()->getPackage()),
                        StringToolkit::asSnakeCase($root_entity->getType()->getName())
                    )
                );
                return true;
            }
        }
        if (array_key_exists('module', $user_parameters)) {
            $module = $user_parameters['module']->getValue();
            if ($module instanceof EntityTypeInterface) {
                $root_type = $module->getRoot() ?: $module;
                $ro = $this->getContext()->getRouting();
                $user_parameters['module'] = $ro->createValue(
                    sprintf(
                        '%s-%s-%s',
                        StringToolkit::asSnakeCase($root_type->getVendor()),
                        StringToolkit::asSnakeCase($root_type->getPackage()),
                        StringToolkit::asSnakeCase($root_type->getName())
                    )
                );
                return true;
            }
        }

        // there might be a 'module' set as a string in the user parameters
        if (!array_key_exists('module', $user_parameters)) {
            throw new InvalidArgumentException(
                'A "resource" or "module" or "attribute" user parameter instance implementing ' .
                'EntityInterface, EntityTypeInterface or AttributeInterface is expected.'
            );
        }

        return true;
    }
}
