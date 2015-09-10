<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Workflux_Visualize_VisualizeSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $aggregate_root_type = $request_data->getParameter('type');
        $state_machine = $aggregate_root_type->getWorkflowStateMachine();
        $svg = $this->renderSubject($state_machine);

        if ($request_data->hasParameter('output')) {
            file_put_contents($request_data->getParameter('output'), $svg);
        } else {
            return $svg;
        }
    }
}
