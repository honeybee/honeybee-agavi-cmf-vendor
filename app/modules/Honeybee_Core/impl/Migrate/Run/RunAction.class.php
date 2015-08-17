<?php

use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\FrameworkBinding\Agavi\Validator\MigrationTargetValidator;

class Honeybee_Core_Migrate_RunAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $service_locator = $this->getServiceLocator();
        $migration_service = $service_locator->getMigrationService();

        $migrated_targets = [];
        if (!$request_data->hasParameter('target')
            || MigrationTargetValidator::ALL_TARGETS === $request_data->getParameter('target')
        ) {
            if ($request_data->hasParameter('version')) {
                throw new RuntimeError("Version parameter only supported together with a valid target.");
            }
            foreach ($migration_service->getMigrationTargetMap() as $target_name => $migration_target) {
                if ($migration_target->isActivated()) {
                    $migrated_targets[$target_name] = [
                        'target' => $migration_target,
                        'migrations' => $migration_service->migrate($target_name)
                    ];
                }
            }
        } else {
            $target_name = $request_data->getParameter('target');
            $target_version = $request_data->getParameter('version');
            $migrated_targets[$target_name] = [
                'target' => $migration_service->getMigrationTarget($target_name),
                'migrations' => $migration_service->migrate($target_name, $target_version)
            ];
        }

        $this->setAttribute('migrated_targets', $migrated_targets);

        return 'Success';
    }
}
