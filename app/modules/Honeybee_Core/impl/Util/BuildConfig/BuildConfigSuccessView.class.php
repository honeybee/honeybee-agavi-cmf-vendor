<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Util_BuildConfig_BuildConfigSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> successfully built configuration includes';

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('includes', []) as $line) {
                $message .= sprintf(PHP_EOL . ' - %s', $line);
            }
        }

        return $this->cliMessage($message);
    }
}
