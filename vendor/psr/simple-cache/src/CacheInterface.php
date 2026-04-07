<?php

namespace ComfinoExternal\Psr\SimpleCache;

interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($key, $default = null);
    /**
     * @param string $key
     * @param mixed $value
     * @param null|int|\DateInterval $ttl
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set($key, $value, $ttl = null);
    /**
     * @param string $key
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete($key);
    /**
     * @return bool
     */
    public function clear();
    /**
     * @param iterable $keys
     * @param mixed $default
     * @return iterable
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMultiple($keys, $default = null);
    /**
     * @param iterable $values
     * @param null|int|\DateInterval $ttl
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null);
    /**
     * @param iterable $keys
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteMultiple($keys);
    /**
     * @param string $key
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function has($key);
}
