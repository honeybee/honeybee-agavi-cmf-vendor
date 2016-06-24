<?php

namespace Honeybee\Tests\Mock;

use Honeybee\Infrastructure\Filesystem\FilesystemService;

class TestFilesystemService extends FilesystemService
{
    protected $test_resources = [];

    public function writeStream($uri, $resource, array $config = [])
    {
        $this->test_resources[$uri] = $resource;
        return true;
    }

    public function getSize($path)
    {
        return fstat($this->test_resources[$path])['size'];
    }

    public function getMimetype($path)
    {
        return $this->guessMimeTypeByExtension(pathinfo($path, PATHINFO_EXTENSION));
    }

    public function getTestResourceUris()
    {
        return array_keys($this->test_resources);
    }

    public function clear()
    {
        $this->test_resources = [];
    }
}