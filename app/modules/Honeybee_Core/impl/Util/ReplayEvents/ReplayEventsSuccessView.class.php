<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Util_ReplayEvents_ReplayEventsSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> successfully replayed events for aggregate root type "%s"%s' . PHP_EOL,
            $request_data->getParameter('type')->getName(),
            $request_data->getParameter('identifier')?' for identifier: '.$request_data->getParameter('identifier'):''
        );

        $distributed_events = $this->getAttribute('distributed_events', []);
        foreach ($distributed_events as $type => $count) {
            $message .= sprintf(
                PHP_EOL . '   Event: %s' . PHP_EOL . '   Status: replayed %d event%s' . PHP_EOL,
                $type,
                $count,
                $count > 1 ? 's' : ''
            );
        }

        return $this->cliMessage($message);
    }
}
