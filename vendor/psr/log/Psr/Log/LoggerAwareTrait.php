<?php

namespace ComfinoExternal\Psr\Log;

trait LoggerAwareTrait
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;
    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
