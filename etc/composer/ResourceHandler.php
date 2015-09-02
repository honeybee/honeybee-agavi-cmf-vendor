<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;

class ResourceHandler
{
    public static function createResource(Event $event)
    {
        $io = $event->getIO();
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.util.generate_code -skeleton honeybee_resource -quiet',
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer, false);
        });

        if ($process->isSuccessful()) {
            $io->write('');
	        $io->write('    When you have configured your entity types you');
	        $io->write('    can build your models using the helper command');
	        $io->write('    line utility:');
	        $io->write('');
	        $io->write('    composer resource-build');
            $io->write('');
        }
    }

    public static function buildResource(Event $event)
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