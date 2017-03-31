<?php

use Honeygavi\App\Base\View;

//use Symfony\Component\Process\Process;

class Honeybee_Core_Util_WatchScss_WatchScssSuccessView extends View
{

    public function executeConsole(\AgaviRequestDataHolder $request_data)
    {
        $processes = $this->getAttribute('processes');

        if (empty($processes)) {
            return "Nothing to watch.\n";
        }

        echo "The following items are watched now: \n\n- " . implode("\n- ", array_keys($processes)) . "\n\n";

        $cmd = implode(
            " & \n\n",
            array_map(
                function($proc) {
                    return $proc->getCommandLine();
                },
                $processes
            )
        );

        if ($request_data->getParameter('verbose', false)) {
            echo "\nRunning the following commands: \n\n$cmd\n\n";
        }

        // FIXME: we use system() here as the symfony process component doesn't seem to like watching sass at work;
        // dunno what ruby does to pipes, but it fails and as long as there's no known solution we will have
        // to stick with shelling out instead of controlling asynchronuous processes here
        system($cmd);

        return "\nDone.\n";
    }

}
