<?php

namespace Honeygavi\ProcessManager;

use Trellis\Common\BaseObjectInterface;

interface ProcessStateInterface extends BaseObjectInterface
{
    public function getUuid();

    public function getPayload();

    public function getProcessName();

    public function getStateName();
}
