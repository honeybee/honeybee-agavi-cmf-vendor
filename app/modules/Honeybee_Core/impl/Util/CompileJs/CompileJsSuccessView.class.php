<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;

class Honeybee_Core_Util_CompileJs_CompileJsSuccessView extends View
{
    public function executeConsole(\AgaviRequestDataHolder $request_data)
    {
        $report = $this->getAttribute('report');

        if ($request_data->getParameter('silent', false)) {
            return;
        }

        $message = 'The following item was compiled:' . PHP_EOL;
        foreach ($report as $file => $data) {
            $message .= $file . PHP_EOL;
            if ($request_data->getParameter('verbose', false)) {
                $message .= 'Command: ' . $data['cmd'] . PHP_EOL;
                if (!empty($data['stdout'])) {
                    $message .= 'STDOUT: ' . PHP_EOL . $data['stdout'] . PHP_EOL;
                }
                if (!empty($data['stderr'])) {
                    $message .= 'STDERR: ' . PHP_EOL . $data['stderr'] . PHP_EOL;
                }
                if (empty($data['stdout']) && empty($data['stderr'])) {
                    $message .= PHP_EOL;
                }
            }
        }

        return $message;
    }
}
