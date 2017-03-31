<?php

use Honeygavi\Agavi\App\Base\View;

class Honeybee_Core_Fixture_Generate_GenerateSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> successfully generated data for %s entities',
            $this->getAttribute('size')
        );

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('report', []) as $line) {
                $message .= sprintf(PHP_EOL . ' - %s', $line);
            }
        }

        if (!$request_data->hasParameter('target')) {
            $message .= PHP_EOL . PHP_EOL . $this->getAttribute('data') . PHP_EOL;
        } else {
            $message .= PHP_EOL . '-> fixture file generated here:' . PHP_EOL . realpath($request_data->getParameter('target'));
        }

        return $this->cliMessage($message);
    }
}
