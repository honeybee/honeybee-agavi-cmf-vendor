<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;
use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Ui\ViewConfig\ViewConfigInterface;

class RelValidator extends AgaviValidator
{
    protected function validate()
    {
        $data = $this->getData($this->getArgument());
        if (!is_string($data)) {
            $this->throwError('type');
            return false;
        }

        $pattern = $this->getParameter('pattern', "#^(?'activity_scope'[\w\.]+)~(?'activity_name'[\w\.]+)$#u");

        $result = preg_match($pattern, $data, $matches);
        if($result !== 1) {
            $this->throwError('format');
            return false;
        }

        $activity_service = $this->getContext()->getServiceLocator()->getActivityService();
        $activity = null;
        try {
            $activity = $activity_service->getActivity($matches['activity_scope'], $matches['activity_name']);
        } catch (RuntimeError $e) {
            $this->throwError('missing');
            return false;
        }

        $this->export($activity, $this->getParameter('export', $this->getArgument() ?: 'activity'));

        return true;
    }
}
