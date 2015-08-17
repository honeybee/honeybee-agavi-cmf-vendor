<?php

namespace Honeybee\SystemAccount\User\Model\Task\MoveUserNode;

use Honeybee\Model\Task\MoveAggregateRootNode\MoveAggregateRootNodeCommand;

class MoveUserNodeCommand extends MoveAggregateRootNodeCommand
{
    public function getEventClass()
    {
        return UserNodeMovedEvent::CLASS;
    }
}
