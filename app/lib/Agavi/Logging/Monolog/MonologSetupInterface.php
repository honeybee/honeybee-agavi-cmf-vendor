<?php

namespace Honeygavi\Agavi\Logging\Monolog;

use AgaviLoggerAppender;

/**
 * Interface that classes should implement that want to return a configured
 * \Monolog\Logger instance for usage by the \Honeygavi\Agavi\Logging\Logger.
 */
interface MonologSetupInterface
{
    /**
     * @param AgaviLoggerAppender $appender instance to use for getting parameters from logging.xml file.
     *
     * @return \Monolog\Logger configured instance with handlers and processors
     */
    public static function getMonologInstance(AgaviLoggerAppender $appender);
}
