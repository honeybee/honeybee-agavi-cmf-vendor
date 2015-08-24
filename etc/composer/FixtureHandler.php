<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;

class FixtureHandler
{
    public static function createFixture(Event $event)
    {
        $io = $event->getIO();
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.fixture.create',
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }

    public static function importFixture(Event $event, $target = null, $fixture = null)
    {
        $target = !empty($target) ? ' -target ' . $target : '';
        $fixture = !empty($fixture) ? ' -fixture ' . $fixture : '';

        $io = $event->getIO();
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.fixture.import' . $target . $fixture,
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }

    public static function generateFixture(Event $event)
    {
        $io = $event->getIO();
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.fixture.generate',
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });
    }
}