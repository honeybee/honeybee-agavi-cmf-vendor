<?php

namespace Honeybee\Tests\Mock;

use Honeybee\FrameworkBinding\Agavi\User\AclSecurityUser;

class TestUser extends AclSecurityUser
{
    public function startup()
    {
        $this->setAttributes([
            'login' => 'administrator',
            'email' => 'administrator@test.com',
            'acl_role' => 'administrator',
            'name' => 'test administrator',
            'identifier' => 'honeybee.system_account.user-539fb03b-9bc3-47d9-886d-77f56d390d94-de_DE-1',
            'background_images' => []
        ]);
        $this->setAuthenticated(true);
    }

    public function shutdown()
    {
        $this->setAuthenticated(false);
        $this->clearAttributes();
        parent::shutdown();
    }
}
