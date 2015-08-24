<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;
use Exception;
use Symfony\Component\Process\ProcessBuilder;

class FixtureHandler
{
    protected static function getProjectPath(Event $event)
    {
        return realpath($event->getComposer()->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . '..');
    }

    public static function createFixture(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            'bin/cli honeybee.core.fixture.create',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }

    public static function importFixture(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            'bin/cli honeybee.core.fixture.import',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }

    public static function generateFixture(Event $event)
    {
        $io = $event->getIO();
        $process = new Process(
            'bin/cli honeybee.core.fixture.generate',
            self::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }
}