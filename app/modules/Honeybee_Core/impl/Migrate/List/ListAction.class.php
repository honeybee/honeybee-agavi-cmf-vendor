<?php

use Honeygavi\App\Base\Action;
use Honeybee\Infrastructure\Migration\MigrationService;

class Honeybee_Core_Migrate_ListAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $service_locator = $this->getServiceLocator();
        $migration_service = $service_locator->getMigrationService();

        $show_only = $request_data->getParameter('only', 'all');
        $migration_targets = [];

        if (!$request_data->hasParameter('target')) {
            foreach ($migration_service->getMigrationTargetMap() as $target_name => $migration_target) {
                $migration_targets[$target_name] = [
                    'target' => $migration_target,
                    'migrations' => $this->getMigrationsForTarget($target_name, $show_only)
                ];
            }
        } else {
            $target_name = $request_data->getParameter('target');
            $migration_targets[$target_name] = [
                'target' => $migration_service->getMigrationTarget($target_name),
                'migrations' => $this->getMigrationsForTarget($target_name, $show_only)
            ];
        }

        $this->setAttribute('only', $show_only);
        $this->setAttribute('migration_targets', $migration_targets);

        return 'Success';
    }

    protected function getMigrationsForTarget($target_name, $show_only)
    {
        $service_locator = $this->getServiceLocator();
        $migration_service = $service_locator->getMigrationService();

        switch ($show_only) {
            case MigrationService::FILTER_PENDING:
                return $migration_service->getPendingMigrations($target_name);
            break;

            case MigrationService::FILTER_EXECUTED:
                return $migration_service->getExecutedMigrations($target_name);
            break;

            default:
                return $migration_service->getMigrationList($target_name);
        }
    }
}
