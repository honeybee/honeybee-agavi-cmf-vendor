<?php

namespace Honeygavi\Security\Acl;

use Trellis\Common\Configurable;
use Honeybee\Model\Aggregate\AggregateRoot;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;

class ExpressionAssert extends Configurable implements AssertionInterface
{
    protected $expression;

    protected $expression_service;

    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        if (!($resource instanceof AggregateRoot)) {
            return false;
        }

        return $this->expression_service->evaluate(
            $this->expression,
            array_merge(
                $this->getOptions()->toArray(),
                array('resource' => $resource, 'user' => $role)
            )
        );
    }
}
