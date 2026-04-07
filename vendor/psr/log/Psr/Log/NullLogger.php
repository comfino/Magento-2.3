<?php

namespace ComfinoExternal\Psr\Log;

class NullLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = array())
    {
    }
}
