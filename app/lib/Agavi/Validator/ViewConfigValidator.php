<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;
use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Ui\ViewConfig\ViewConfigInterface;

class ViewConfigValidator extends AgaviValidator
{
    const DEFAULT_EXPORT = 'view_config';

    protected function validate()
    {
        $success = true;
        $view_config = null;
        $argument_name = $this->getArgument();
        if ($argument_name) {
            $view_config = $this->getData($argument_name);
            if (!$view_config instanceof ViewConfigInterface) {
                $this->throwError('type');
                $success = false;
            }
        } else {
            $view_config = $this->getViewConfig();
        }

        if ($success) {
            $this->export(
                $view_config,
                $this->getParameter(
                    'export',
                    $this->getArgument() ?: self::DEFAULT_EXPORT
                )
            );
        }

        return $success;
    }

    /**
     * Loads display mode specific view config and falls back to exactly the
     * given "view_scope" parameter value as scope.
     *
     * @param string $display_mode valid display mode string like 'table' or 'grid'
     *
     * @return ViewConfigInterface of view configuration
     *
     * @throws RuntimeError when given "view_scope" cannot be found in views.xml file
     */
    protected function getViewConfig($display_mode = '')
    {
        $scope = $this->getParameter('view_scope');
        if (empty($scope)) {
            return [];
        }

        $view_config_service = $this->getContext()->getServiceLocator()->getViewConfigService();

        $specific_scope = $scope . '.' . $display_mode;

        $view_config = $view_config_service->getViewConfig($specific_scope);
        if (empty($view_config)) {
            $view_config = $view_config_service->getViewConfig($scope);
        }

        if (empty($view_config)) {
            throw new RuntimeError(
                sprintf(
                    'Unable to find view config for scope "%s" (or fallback scope "%s").',
                    $specific_scope,
                    $scope
                )
            );
        }

        return $view_config;
    }
}
