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

    public static function importFixture(Event $event)
    {
        $args = ScriptToolkit::processArguments($event->getArguments());
        $target = isset($args['target']) ? ' -target ' . $args['target'] : '';
        $fixture = isset($args['fixture']) ? ' -fixture ' . $args['fixture'] : '';

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