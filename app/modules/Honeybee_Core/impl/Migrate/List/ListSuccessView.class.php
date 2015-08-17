<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\Migration\MigrationInterface;
use Honeybee\Infrastructure\Migration\MigrationService;
use Honeybee\Infrastructure\Migration\MigrationTargetInterface;

class Honeybee_Core_Migrate_List_ListSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $output_lines = [];
        $migration_targets = $this->getAttribute('migration_targets');
        $target_count = count($migration_targets);
        $cur_count = 0;
        foreach ($migration_targets as $migration_target_info) {
            // don't display empty targets, when filtering for executed or pending migrations
            if ($this->getAttribute('only') !== 'all' && count($migration_target_info['migrations']) === 0) {
                $target_count--;
                $cur_count++;
                continue;
            }
            if ($cur_count > 0 && $cur_count < $target_count) {
                $output_lines[] = str_repeat('-', 50);
            }
            $output_lines = array_merge(
                $output_lines,
                $this->renderMigrationTargetInfo($migration_target_info)
            );
            $cur_count++;
        }

        $this->cliMessage(join(PHP_EOL, $output_lines) . PHP_EOL);
    }

    protected function renderMigrationTargetInfo(array $migration_target_info)
    {
        $migrations = $migration_target_info['migrations'];
        $migration_target = $migration_target_info['target'];

        $title_template = "Target: %s\nActive: %s\n";
        if (count($migrations) === 0) {
            $title_template .= PHP_EOL . '* This target does not have migrations yet.';
        }

        $output_lines = [
            sprintf(
                PHP_EOL . $title_template,
                $migration_target->getName(),
                $migration_target->isActivated() ? 'true' : 'false'
            )
        ];

        $filter_by = $this->getAttribute('only');
        $executed_versions = $this->getExecutedMigrationVersions($migration_target);
        foreach ($migrations as $cur_count => $migration) {
            $migration_title_tpl = '% 3d) Version: %s %s';
            $migration_title_args = [ $cur_count + 1, $migration->getVersion(), $migration->getName() ];
            if ($filter_by === 'all') {
                if (isset($executed_versions[$migration->getVersion()])) {
                    $migration_title_tpl .= "\n      Status : executed at %s";
                    $migration_title_args[] = $executed_versions[$migration->getVersion()];
                } else {
                    $migration_title_tpl .= "\n      Status : pending";
                }
            }

            $output_lines[] = vsprintf($migration_title_tpl, $migration_title_args);

            if ($filter_by === MigrationService::FILTER_EXECUTED) {
                $output_lines[] = sprintf('      Desc.  : %s' . PHP_EOL, $migration->getDescription(MigrationInterface::MIGRATE_DOWN));
            } else {
                $output_lines[] = sprintf('      Desc.  : %s' . PHP_EOL, $migration->getDescription(MigrationInterface::MIGRATE_UP));
            }
        }

        return $output_lines;
    }

    protected function getExecutedMigrationVersions(MigrationTargetInterface $migration_target)
    {
        $executed_versions = [];
        foreach ($migration_target->getStructureVersionList() as $structure_version) {
            $executed_versions[$structure_version->getVersion()] = $structure_version->getCreatedDate();
        }

        return $executed_versions;
    }
}
