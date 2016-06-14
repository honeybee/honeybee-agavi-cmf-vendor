<?php

namespace Honeybee\FrameworkBinding\Agavi\Logging\Monolog;

use Monolog\Logger;
use Monolog\Handler\TestHandler;
use AgaviLoggerAppender;

/**
 * Returns a configured \Monolog\Logger instance that logs all messages above
 * DEBUG level to memory.
 *
 * Supported appender parameters:
 * - minimum_level: Minimum \Monolog\LogLevel to log. Defaults to DEBUG
 * - channel: The channel name to use for logging. Defaults to appender name.
 * - bubble: Boolean value to specify whether messages that are handled should
 *           bubble up the stack or not. Defaults to true.
 */
class TestSetup implements MonologSetupInterface
{
    /**
     * @param AgaviLoggerAppender $appender Agavi logger appender instance to use for Monolog\Logger instance creation
     *
     * @return Logger with Monolog\Handler\TestHandler
     */
    public static function getMonologInstance(AgaviLoggerAppender $appender)
    {
        $minimum_level = $appender->getParameter('minimum_level', Logger::DEBUG);
        $bubble = $appender->getParameter('bubble', true);
        $channel_name = $appender->getParameter('channel', $appender->getParameter('name', 'monolog-default'));

        $logger = new Logger($channel_name);
        $logger->pushHandler(new TestHandler($minimum_level, $bubble));

        return $logger;
    }
}
