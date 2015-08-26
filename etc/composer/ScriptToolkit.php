<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use RecursiveIteratorIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use Exception;

class ScriptToolkit
{
    const PROCESS_TIMEOUT = 3600;

    const DIRECTORY_MODE = 0755;

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

    public static function makeDirectory($path, $mode = self::DIRECTORY_MODE)
    {
        $fs = new Filesystem();
        $fs->mkdir($path, $mode);
    }

    public static function removeDirectoryContents($path)
    {
        $files = array_diff(scandir($path), [ '.', '..', '.gitignore' ]);
        $fs = new Filesystem();
        $fs->remove($files);
    }

    public static function processArguments(array $arguments)
    {
        $processed = [];
        foreach($arguments as $argument) {
            if (preg_match('/^--.+/', $argument) && strpos($argument, '=') === false) {
                $processed[substr($argument, 2)] = true;
            }  elseif (preg_match('/^-[^-].+/', $argument) && strpos($argument, '=') >= 2) {
                $parts = explode('=', $argument);
                $processed[substr($parts[0], 1)] = $parts[1];
            } else {
                throw new Exception('Invalid argument, format should be --arg or -arg=value.');
            }
        }
        return $processed;
    }

    /*
     * Cannot use Filesystem::mirror because it doesn't handle symlinks properly.
     * Symlinking will be an issue that needs resolving on Windows systems
     */
    public static function copyDirectory($source, $dest)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                function ($current) {
                    // Skip hidden folders & files
                    return strpos($current->getFilename(), '.') !== 0;
                }
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $fs = new Filesystem();
        if ($fs->exists($source)) {
            $fs->mkdir($dest, self::DIRECTORY_MODE);
        }

        foreach ($iterator as $item) {
            $target = str_replace($source, $dest, $item->getPathname());
            if (is_link($item)) {
                $fs->symlink($item->getLinkTarget(), $target);
            } elseif (is_dir($item)) {
                $fs->mkdir($target, self::DIRECTORY_MODE);
            } elseif (is_file($item)) {
                $fs->copy($item, $target, true);
            }
        }
    }
}