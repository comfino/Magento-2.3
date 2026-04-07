<?php

namespace ComfinoExternal\Psr\Log;

abstract class AbstractLogger implements LoggerInterface
{
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
