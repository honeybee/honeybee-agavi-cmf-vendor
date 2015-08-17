<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\JsonToolkit;

class Honeybee_Core_Fixture_GenerateAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $size = $request_data->getParameter('size', 1);
        $aggregate_root_type = $request_data->getParameter('type');

        $fixture_service = $this->getServiceLocator()->getFixtureService();
        $type_prefix = $aggregate_root_type->getPrefix();
        $documents[$type_prefix] = $fixture_service->generate($type_prefix, $size);

        $json = json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $errors = [];
        $report = [];

        $success = true;
        if ($request_data->hasParameter('target')) {
            $target = $request_data->getParameter('target');
            if (is_writable(dirname($target))) {
                $success = (file_put_contents($target, $json, LOCK_EX) !== false);
                if (!$success) {
                    $errors[] = sprintf('failed to write to: %s', $target);
                }
            } else {
                $errors[] = sprintf('target filename is not writable: %s', $target);
                $success = false;
            }
        } else {
            $this->setAttribute('data', $json);
        }

        $this->setAttribute('report', $report);
        $this->setAttribute('size', count($documents[$type_prefix]));

        if (!$success) {
            $this->setAttribute('errors', $errors);
            return 'Error';
        }

        return 'Success';
    }
}
