<?php

namespace Honeygavi\Ui\Navigation;

use Honeygavi\Ui\Activity\ActivityInterface;
use Trellis\Common\BaseObject;

class NavigationItem extends BaseObject implements NavigationItemInterface
{
    protected $activity;

    public function __construct(ActivityInterface $activity)
    {
        $this->activity = $activity;
    }

    public function getActivity()
    {
        return $this->activity;
    }
}
