<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Trellis_GenerateCode_GenerateCodeErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = '-> failure generating Trellis code';

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('errors', []) as $error) {
                $message .= sprintf(PHP_EOL . ' - %s', $error);
            }
        }

        return $this->cliError($message);
    }
}
