<?php

namespace ComfinoExternal\Psr\Log;

interface LoggerAwareInterface
{
    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger);
}
