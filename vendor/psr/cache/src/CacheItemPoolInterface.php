<?php

namespace ComfinoExternal\Psr\Cache;

interface CacheItemPoolInterface
{
    /**
     * @param string $key
     * @throws InvalidArgumentException
     * @return CacheItemInterface
     */
    public function getItem($key);
    /**
     * @param string[] $keys
     * @throws InvalidArgumentException
     * @return array|\Traversable
     */
    public function getItems(array $keys = array());
    /**
     * @param string $key
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasItem($key);
    /**
     * @return bool
     */
    public function clear();
    /**
     * @param string $key
     * @throws InvalidArgumentException
     * @return bool
     */
    public function deleteItem($key);
    /**
     * @param string[] $keys
     * @throws InvalidArgumentException
     * @return bool
     */
    public function deleteItems(array $keys);
    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    public function save(CacheItemInterface $item);
    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item);
    /**
     * @return bool
     */
    public function commit();
}
