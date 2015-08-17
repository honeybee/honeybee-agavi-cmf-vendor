<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Trellis_GenerateCode_GenerateCodeSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = sprintf(
            '-> successfully generated Trellis code for target "%s"',
            $request_data->getParameter('target')
        );

        if ($this->hasAttribute('mapping_target_path')) {
            $message .= PHP_EOL . '-> Elasticsearch mapping was created here:';
            $message .= PHP_EOL . $this->getAttribute('mapping_target_path') . PHP_EOL;
        }

        if (!$request_data->getParameter('quiet')) {
            foreach ($this->getAttribute('report', []) as $line) {
                $message .= sprintf(PHP_EOL . ' - %s', $line);
            }
        }

        return $this->cliMessage($message);
    }
}
