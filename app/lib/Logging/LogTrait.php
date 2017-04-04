<?php

namespace Honeygavi\Logging;

use AgaviContext;
use AgaviLogger;

/**
 * Trait to ease logging to specific loggers.
 */
trait LogTrait
{
    // @codeCoverageIgnoreStart
    public function getLoggerName()
    {
        return 'default';
    }

    public function logTrace()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::TRACE,
            get_class($this),
            func_get_args()
        );
    }

    public function logDebug()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::DEBUG,
            get_class($this),
            func_get_args()
        );
    }

    public function logInfo()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::INFO,
            get_class($this),
            func_get_args()
        );
    }

    public function logNotice()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::NOTICE,
            get_class($this),
            func_get_args()
        );
    }

    public function logWarning()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::WARNING,
            get_class($this),
            func_get_args()
        );
    }

    public function logError()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::ERROR,
            get_class($this),
            func_get_args()
        );
    }

    public function logCritical()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::CRITICAL,
            get_class($this),
            func_get_args()
        );
    }

    public function logAlert()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::ALERT,
            get_class($this),
            func_get_args()
        );
    }

    public function logEmergency()
    {
        AgaviContext::getInstance()->getLoggerManager()->logTo(
            $this->getLoggerName(),
            AgaviLogger::EMERGENCY,
            get_class($this),
            func_get_args()
        );
    }
    // @codeCoverageIgnoreEnd
}
