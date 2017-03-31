<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_Core_Workflux_Visualize_VisualizeSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $state_machine = $request_data->getParameter('subject');
        $svg = $this->renderSubject($state_machine);

        if ($request_data->hasParameter('output')) {
            $output = $request_data->getParameter('output');
            file_put_contents($output, $svg);
            $message = sprintf(
                '-> successfully generated visualization for "%s"' . PHP_EOL .
                '-> image was generated here: %s',
                $state_machine->getName(),
                realpath($output)
            );
            return $this->cliMessage($message);
        } else {
            return $svg;
        }
    }
}
