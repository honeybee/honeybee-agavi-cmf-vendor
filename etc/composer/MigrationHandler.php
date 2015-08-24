<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;

class MigrationHandler
{
    protected static function getProjectPath(Event $event)
    {
        return realpath($event->getComposer()->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . '..');
    }

    public static function createMigration(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            'bin/cli honeybee.core.migrate.create',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }

    public static function listMigrations(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            'bin/cli honeybee.core.migrate.list',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }

    public static function runAllMigrations(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            './bin/cli honeybee.core.migrate.run -target all',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }

    public static function runMigration(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            './bin/cli honeybee.core.migrate.run',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }
}