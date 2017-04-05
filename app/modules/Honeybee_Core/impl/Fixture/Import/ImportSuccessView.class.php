<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Fixture_Import_ImportSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> successfully imported data from fixture "%s"',
            $request_data->getParameter('fixture')
        );

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('report', []) as $line) {
                $message .= sprintf(PHP_EOL . ' - %s', $line);
            }
        }

        return $this->cliMessage($message);
    }
}
