<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;

class ModuleHandler
{
    public static function createModule(Event $event)
    {
        $io = $event->getIO();
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.util.generate_code -skeleton honeybee_module -quiet',
            ScriptToolkit::getProjectPath($event)
        );

        $process->run(function ($type, $buffer) use($io) {
            $io->write($buffer, false);
        });

        if ($process->isSuccessful()) {
            $io->write('<info>');
            $io->write('    You can now quickly scaffold new types into this');
            $io->write('    module using the helper command line utility:');
            $io->write('');
            $io->write('    bin/composer.phar type-create');
            $io->write('</>');
        }
    }
}