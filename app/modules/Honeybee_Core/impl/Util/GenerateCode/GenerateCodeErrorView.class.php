<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Util_GenerateCode_GenerateCodeErrorView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> failure generating code for skeleton "%s"',
            $request_data->getParameter('skeleton')
        );

        if (!$request_data->getParameter('quiet')) {
            $message .=  PHP_EOL . ' - ' . $this->getAttribute('errors');
        }

        return $this->cliError($message);
    }
}
