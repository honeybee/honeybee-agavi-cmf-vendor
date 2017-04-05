<?php

namespace Honeygavi\Security\Acl\Permission;

interface PermissionInterface
{
    public function getName();

    public function getAccessScope();

    public function getAccessType();

    public function getType();

    public function getExpression();
}
