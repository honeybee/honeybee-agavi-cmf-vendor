<?php

namespace Honeygavi\App\ActionPack\Resource\Task;

use Honeygavi\App\Base\View;
use AgaviRequestDataHolder;

class TaskSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        return $this->createWorkflowForwardContainer($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        return $this->createWorkflowForwardContainer($request_data);
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        return $this->createWorkflowForwardContainer($request_data);
    }

    protected function createWorkflowForwardContainer(AgaviRequestDataHolder $request_data)
    {
        $container_def = $this->getAttribute('task_info');

        $required_keys = [ 'module', 'action' ];
        foreach ($required_keys as $required_key) {
            if (!isset($container_def[$required_key])) {
                throw new RuntimeError(sprintf('Missing required task parameter "%s".', $required_key));
            }
        }

        $defaults = [ 'arguments' => null, 'output_type' => null, 'request_method' => null ];
        $container_def = array_merge($defaults, $container_def);
/*
        if (!array_key_exists('resource', $container_def['arguments'])) {
            $container_def['arguments']['resource'] = $request_data->getParameter('resource');
        }
*/
        return $this->createForwardContainer(
            $container_def['module'],
            $container_def['action'],
            $container_def['arguments'],
            $container_def['output_type'],
            $container_def['request_method']
        );
    }
}
