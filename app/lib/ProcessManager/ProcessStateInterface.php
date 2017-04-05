<?php

namespace Honeygavi\ProcessManager;

use Trellis\Common\ObjectInterface;

interface ProcessStateInterface extends ObjectInterface
{
    public function getUuid();

    public function getPayload();

    public function getProcessName();

    public function getStateName();
}
