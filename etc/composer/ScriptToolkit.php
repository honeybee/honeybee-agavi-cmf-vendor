<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

class ScriptToolkit
{
    const PROCESS_TIMEOUT = 3600;

    public static function getProjectPath(Event $event)
    {
        return realpath($event->getComposer()->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . '..');
    }

    public static function createProcess($command, $cwd)
    {
        $process = new Process($command, $cwd);
        $process->setTimeout(self::PROCESS_TIMEOUT);
        return $process;
    }

    public static function removeDirectoryContents($path)
    {
        if (is_writable($path)) {
            $files = array_diff(scandir($path), [ '.', '..', '.gitignore', '.gitkeep' ]);
            foreach ($files as $file) {
                $item_path = $path . DIRECTORY_SEPARATOR . $file;
                is_dir($item_path) ? self::removeDirectory($item_path) : unlink($item_path);
            }
        }
    }

    public static function removeDirectory($path)
    {
        if (is_writable($path)) {
            $files = array_diff(scandir($path), [ '.', '..' ]);
            foreach ($files as $file) {
                $item_path = $path . DIRECTORY_SEPARATOR . $file;
                is_dir($item_path) ? self::removeDirectory($item_path) : unlink($item_path);
            }
            rmdir($path);
        }
    }
}