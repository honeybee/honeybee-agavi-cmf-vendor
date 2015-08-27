<?php

namespace HoneybeeExtensions\Composer;

use Composer\Script\Event;

class ConfigurationHandler
{
    public static function buildConfig(Event $event)
    {
        $io = $event->getIO();
        $project_path = ScriptToolkit::getProjectPath($event);
        ScriptToolkit::removeDirectoryContents($project_path . '/app/config/includes');
        $process = ScriptToolkit::createProcess(
            'bin/cli honeybee.core.util.build_config --recovery -quiet',
            $project_path
        );

        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        self::clearCaches($event);
        self::makeAutoload($event);
    }

    public static function clearCaches(Event $event)
    {
        $io = $event->getIO();
        $project_path = ScriptToolkit::getProjectPath($event);
        ScriptToolkit::removeDirectoryContents($project_path . '/app/cache');
        $io->write('-> cleared application caches');
    }

    public static function makeAutoload(Event $event)
    {
        $composer = $event->getComposer();
        $event->getComposer()
            ->getAutoloadGenerator()
            ->dump(
                $composer->getConfig(),
                $composer->getRepositoryManager()->getLocalRepository(),
                $composer->getPackage(),
                $composer->getInstallationManager(),
                'composer', // = default
                true        // = optimize
            );
        $event->getIO()->write('-> generated composer autoload files');
    }
}