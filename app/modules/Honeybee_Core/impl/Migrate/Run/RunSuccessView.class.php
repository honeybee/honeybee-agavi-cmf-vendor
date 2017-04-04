<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Migrate_Run_RunSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> successfully executed migrations' . PHP_EOL;

        $migrated_targets = $this->getAttribute('migrated_targets', []);
        foreach ($migrated_targets as $target_name => $migrated_target) {
            $migration_list = $migrated_target['migrations'];
            $migration_count = $migration_list->getSize();

            if ($migration_count > 0) {
                $message .= sprintf(
                    PHP_EOL . '   Target: %s' . PHP_EOL . '   Status: executed %d migration%s' . PHP_EOL,
                    $target_name,
                    $migration_count,
                    $migration_count > 1 ? 's' : ''
                );
            } else {
                $message .= sprintf(
                    PHP_EOL . '   Target: %s' . PHP_EOL . '   Status: no migrations to execute' . PHP_EOL,
                    $target_name
                );
            }
        }

        return $this->cliMessage($message);
    }
}
