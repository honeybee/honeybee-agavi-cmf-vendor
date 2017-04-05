<?php

namespace Honeygavi\Ui\Navigation;

use Trellis\Common\Object;
use Honeygavi\Ui\Activity\ActivityInterface;

class NavigationItem extends Object implements NavigationItemInterface
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
