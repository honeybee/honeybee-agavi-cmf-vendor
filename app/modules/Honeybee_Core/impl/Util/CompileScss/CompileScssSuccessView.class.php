<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_Util_CompileScss_CompileScssSuccessView extends View
{
    public function executeConsole(\AgaviRequestDataHolder $request_data)
    {
        $report = $this->getAttribute('report');

        if ($request_data->getParameter('silent', false)) {
            return;
        }

        $message = 'The following items were compiled:' . PHP_EOL . PHP_EOL;
        foreach ($report as $directory => $data) {
            $message .= '- ' . $data['name'] . PHP_EOL;
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
                if (!empty($data['autoprefixer']['stdout'])) {
                    $message .= 'Autoprefixer STDOUT: ' . PHP_EOL . $data['autoprefixer']['stdout'];
                }
                if (!empty($data['autoprefixer']['stderr'])) {
                    $message .= 'Autoprefixer STDERR: ' . PHP_EOL . $data['autoprefixer']['stderr'];
                }
                if (empty($data['autoprefixer']['autoprefixer']['stdout']) && empty($data['autoprefixer']['stderr'])) {
                    $message .= PHP_EOL;
                }
            }
        }

        return $message . PHP_EOL;
    }
}
