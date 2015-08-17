<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Util_BuildConfig_BuildConfigErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> failure trying to build include files';

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('errors', []) as $error) {
                $message .= sprintf(PHP_EOL . ' - %s', $error);
            }
        }

        return $this->cliError($message);
    }
}
