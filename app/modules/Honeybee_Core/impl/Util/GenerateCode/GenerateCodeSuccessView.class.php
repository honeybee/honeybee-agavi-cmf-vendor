<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Util_GenerateCode_GenerateCodeSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> successfully generated code from skeleton "%s"',
            $request_data->getParameter('skeleton')
        );

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('report', []) as $line) {
                $message .= sprintf(PHP_EOL . ' - %s', $line);
            }
        }

        return $this->cliMessage($message);
    }
}
