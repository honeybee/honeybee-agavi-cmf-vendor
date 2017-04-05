<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Util_ReplayEvents_ReplayEventsErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> failure trying to replay events for aggregate root type "%s"',
            $request_data->getParameter('type')->getName()
        );

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('errors', []) as $line) {
                $message .= sprintf(PHP_EOL . ' - %s', $error);
            }
        }

        return $this->cliError($message);
    }
}
