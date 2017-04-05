<?php

namespace Honeygavi\Filter;

use AgaviExecutionFilter;
use AgaviExecutionContainer;

/**
 * This filter registers all affected modules of view executions
 * on the ModuleAssetsResponseFilter as that filter includes
 * the necessary default javascripts and styles in HTML responses.
 */
class ExecutionFilter extends AgaviExecutionFilter
{
    protected function executeView(AgaviExecutionContainer $container)
    {
        $view_result = parent::executeView($container);

        ModuleAssetsResponseFilter::addModule(
            $container->getViewModuleName(),
            $container->getOutputType()->getName()
        );

        return $view_result;
    }
}
