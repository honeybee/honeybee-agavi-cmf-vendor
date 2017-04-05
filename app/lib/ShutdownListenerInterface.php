<?php

namespace Honeygavi;

/**
 * Implement this interface and register yourself to the Honeygavi\Context
 * to be notified of shutdowns caused by e.g. Fatal Errors. You will be notified
 * of shutdowns and can free resources or do other graceful things.
 */
interface ShutdownListenerInterface
{
    /**
     * Default scope to use. All handlers are notified for all errors.
     */
    const NOTIFY_SCOPE_GLOBAL = 'global';

    /**
     * Scope to use if your handle should only be notified if fatals happen in
     * your class' inheritance hierarchy.
     */
    const NOTIFY_SCOPE_INSTANCE = 'instance';

    /**
     * Implement this method to gracefully shutdown your class. Usually you
     * should return false to let other registered handlers continue as well.
     *
     * @see register_shutdown_function()
     * @see error_get_last()
     * @see Honeygavi\Context
     *
     * @param array $error associative array with keys "type", "message", "file" and "line"
     *
     * @return boolean true to stop propagation of shutdown to other registered shutdown handlers
     */
    public function onShutdown($error);
}
