<?php

namespace Honeygavi\Ui\Activity;

interface ActivityContainerInterface
{
    public function getScope();

    public function getActivityMap();

    public function getActivityByName($name);
}
