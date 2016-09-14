<?php

namespace Honeybee\SystemAccount\User\Projection\Standard;

use Honeybee\SystemAccount\User\Projection\Standard\Base\User as BaseUser;

class User extends BaseUser
{
    public function getDefaultBackgroundImage()
    {
        $background_images = $this->getBackgroundImages();

        if (count($background_images)) {
            return $background_images[0];
        }
    }
}
