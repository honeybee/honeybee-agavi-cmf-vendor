<?php

namespace Honeybee\FrameworkBinding\Agavi\Filter;

use AgaviExecutionFilter;
use AgaviExecutionContainer;

class DebugExecutionFilter extends AgaviExecutionFilter
{
    protected function executeView(AgaviExecutionContainer $container)
    {
        $view_result = parent::executeView($container);

        ModuleResourcesResponseFilter::addModule(
            $container->getViewModuleName(),
            $container->getOutputType()->getName()
        );

        return $view_result;
    }
}
