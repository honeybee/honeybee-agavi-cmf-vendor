<?php

namespace Honeybee\SystemAccount\User\Model\Aggregate;

use Honeybee\SystemAccount\User\Model\Aggregate\Base\User as BaseUser;
use Honeybee\SystemAccount\User\Model\Task\SetUserAuthToken\SetUserAuthTokenCommand;
use Honeybee\SystemAccount\User\Model\Task\SetUserPassword\SetUserPasswordCommand;

class User extends BaseUser
{
    public function enablePasswordReset(SetUserAuthTokenCommand $command)
    {
        $attribute_changes = [
            'auth_token' => $command->getAuthToken(),
            'token_expire_date' => $command->getTokenExpireDate()
        ];

        $this->modifyAttributesThrough($command, $attribute_changes);
    }

    public function changePassword(SetUserPasswordCommand $command)
    {
        $attribute_changes = [ 'password_hash' => $command->getPasswordHash() ];

        $this->modifyAttributesThrough($command, $attribute_changes);
    }
}
