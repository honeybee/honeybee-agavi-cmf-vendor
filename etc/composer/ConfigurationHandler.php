<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

class ConfigurationHandler
{
    protected static function getProjectPath(Event $event)
    {
        return realpath($event->getComposer()->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . '..');
    }

    public static function buildConfig(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            'bin/cli honeybee.core.util.build_config --recovery -quiet',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            if (!empty(trim($buffer))) $io->write($buffer);
        });

        $io->write('<info>-> built and included configuration files</>');
    }

    public static function clearCaches(Event $event)
    {
        $io = $event->getIO();
        $project_path = self::getProjectPath($event);
        self::removeDirectory($project_path . '/app/cache', true);
        $io->write('<info>-> cleared application caches</>');
    }

    public static function makeAutoload(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            'bin/composer.phar dump-autoload -o -q',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer);
        });

        $io->write('<info>-> regenerated and optimized autoload files</>');
    }

    protected static function removeDirectory($path, $keep = false)
    {
        if (is_writable($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                $item_path = $path . DIRECTORY_SEPARATOR . $file;
                is_dir($item_path) ? self::removeDirectory($item_path) : unlink($item_path);
            }
            rmdir($path);

            if (true === $keep) {
                mkdir($path);
            }
        }
    }

}