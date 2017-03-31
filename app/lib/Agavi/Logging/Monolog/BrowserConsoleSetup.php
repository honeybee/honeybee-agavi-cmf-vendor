<?php

namespace Honeygavi\Agavi\Logging\Monolog;

use Monolog\Logger;
use Monolog\Handler\BrowserConsoleHandler;
use AgaviLoggerAppender;

class BrowserConsoleSetup implements MonologSetupInterface
{
    /**
     * @param AgaviLoggerAppender $appender Agavi logger appender instance to use for Monolog\Logger instance creation
     *
     * @return Logger with Monolog\Handler\BrowserConsoleHandler
     */
    public static function getMonologInstance(AgaviLoggerAppender $appender)
    {
        $minimum_level = $appender->getParameter('minimum_level', Logger::DEBUG);
        $bubble = $appender->getParameter('bubble', true);
        $channel_name = $appender->getParameter('channel', $appender->getParameter('name', 'monolog-default'));

        $logger = new Logger($channel_name);
        $logger->pushHandler(new BrowserConsoleHandler($minimum_level, $bubble));

        return $logger;
    }
}
