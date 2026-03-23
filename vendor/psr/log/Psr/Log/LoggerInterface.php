<?php

namespace ComfinoExternal\Psr\Log;

interface LoggerInterface
{
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function emergency($message, array $context = array());
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function alert($message, array $context = array());
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function critical($message, array $context = array());
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function error($message, array $context = array());
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function warning($message, array $context = array());
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function notice($message, array $context = array());
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function info($message, array $context = array());
    /**
     * @param string $message
     * @param mixed[] $context
     * @return void
     */
    public function debug($message, array $context = array());
    /**
     * @param mixed $level
     * @param string $message
     * @param mixed[] $context
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = array());
}
