<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;

class TypeHandler
{
    public static function createType(Event $event)
    {
        $io = $event->getIO();
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.util.generate_code -skeleton honeybee_type -quiet',
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer, false);
        });

        if ($process->isSuccessful()) {
            $io->write('<info>');
	        $io->write('    When you have updated your type attributes and reference');
	        $io->write('    definitions you can generatr your classes using the');
	        $io->write('    helper command line utility:');
	        $io->write('');
	        $io->write('    bin/composer.phar type-build');
            $io->write('</>');
        }
    }

    public static function buildType(Event $event)
    {
        $io = $event->getIO();
        $args = ScriptToolkit::processArguments($event->getArguments());
        $target = isset($args['target']) ? ' -target ' . $args['target'] : '';
        $target = isset($args['all']) ? ' -target all' : $target;

        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.trellis.generate_code -quiet' . $target,
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer, false);
        });
    }
}