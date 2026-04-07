<?php

namespace ComfinoExternal\Psr\Cache;

interface CacheItemInterface
{
    /**
     * @return string
     */
    public function getKey();
    /**
     * @return mixed
     */
    public function get();
    /**
     * @return bool
     */
    public function isHit();
    /**
     * @param mixed $value
     * @return static
     */
    public function set($value);
    /**
     * @param \DateTimeInterface|null $expiration
     * @return static
     */
    public function expiresAt($expiration);
    /**
     * @param int|\DateInterval|null $time
     * @return static
     */
    public function expiresAfter($time);
}
