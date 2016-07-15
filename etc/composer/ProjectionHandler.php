<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;

class ProjectionHandler
{
    public static function createProjection(Event $event)
    {
        $io = $event->getIO();
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.util.generate_code -skeleton honeybee_projection -quiet',
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer, false);
        });

        if ($process->isSuccessful()) {
            $io->write('');
	        $io->write('    When you have configured your projections you');
	        $io->write('    can build your models using the helper command');
	        $io->write('    line utility:');
	        $io->write('');
	        $io->write('    composer resource-build');
            $io->write('');
        }
    }
}