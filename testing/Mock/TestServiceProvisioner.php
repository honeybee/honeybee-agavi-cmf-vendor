<?php

namespace Honeybee\Tests\Mock;

use AgaviConfig;
use AgaviConfigCache;
use AgaviContext;
use Auryn\Injector as DiContainer;
use Honeybee\FrameworkBinding\Agavi\ServiceProvisioner;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\SystemAccount\User\Model\Aggregate\UserType as UserAggregateType;
use Honeybee\SystemAccount\User\Projection\Standard\UserType as UserProjectionType;
use Workflux\Builder\XmlStateMachineBuilder;

class TestServiceProvisioner extends ServiceProvisioner
{
    public function __construct(DiContainer $di_container)
    {
        $this->di_container = $di_container;

        $this->service_map = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::SERVICES_CONFIG_NAME,
            AgaviContext::getInstance()->getName()
        );

        // @todo build art and pt map from file or fallback to config or static
        $this->aggregate_root_type_map = new AggregateRootTypeMap;
        $test_ar_user_type = new UserAggregateType($this->getDefaultStateMachine());
        $this->aggregate_root_type_map->setItem('honeybee.system_account.user', $test_ar_user_type);
        $this->projection_type_map = new ProjectionTypeMap;
        $test_pr_user_type = new UserProjectionType($this->getDefaultStateMachine());
        $this->projection_type_map->setItem('honeybee.system_account.user', $test_pr_user_type);

        $this->di_container->share($this->service_map);
        $this->di_container->share($this->aggregate_root_type_map);
        $this->di_container->share($this->projection_type_map);

        $this->provisioned_services = [];
    }

    protected function getDefaultStateMachine()
    {
        return (new XmlStateMachineBuilder([
            'name' => 'honeybee_system_account_user_workflow_default',
            'state_machine_definition' => AgaviConfig::get('core.module_dir') . '/Honeybee_SystemAccount/config/User/workflows.xml'
        ]))->build();
    }
}
