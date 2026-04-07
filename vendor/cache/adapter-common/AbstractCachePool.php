<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace ComfinoExternal\Cache\Adapter\Common;

use ComfinoExternal\Cache\Adapter\Common\Exception\CacheException;
use ComfinoExternal\Cache\Adapter\Common\Exception\CachePoolException;
use ComfinoExternal\Cache\Adapter\Common\Exception\InvalidArgumentException;
use ComfinoExternal\Psr\Cache\CacheItemInterface;
use ComfinoExternal\Psr\Log\LoggerAwareInterface;
use ComfinoExternal\Psr\Log\LoggerInterface;
use ComfinoExternal\Psr\SimpleCache\CacheInterface;

abstract class AbstractCachePool implements PhpCachePool, LoggerAwareInterface, CacheInterface
{
    const SEPARATOR_TAG = '!';
    /**
     * @type LoggerInterface
     */
    private $logger;
    /**
     * @type PhpCacheItem[] deferred
     */
    protected $deferred = [];
    /**
     * @param PhpCacheItem $item
     * @param int|null $ttl
     * @return bool
     */
    abstract protected function storeItemInCache(PhpCacheItem $item, $ttl);
    /**
     * @param string $key
     * @return array
     */
    abstract protected function fetchObjectFromCache($key);
    /**
     * @return bool
     */
    abstract protected function clearAllObjectsFromCache();
    /**
     * @param string $key
     * @return bool
     */
    abstract protected function clearOneObjectFromCache($key);
    /**
     * @param string $name
     * @return array
     */
    abstract protected function getList($name);
    /**
     * @param string $name
     * @return bool
     */
    abstract protected function removeList($name);
    /**
     * @param string $name
     * @param string $key
     */
    abstract protected function appendListItem($name, $key);
    /**
     * @param string $name
     * @param string $key
     */
    abstract protected function removeListItem($name, $key);
    
    public function __destruct()
    {
        $this->commit();
    }
    
    public function getItem($key)
    {
        $this->validateKey($key);
        if (isset($this->deferred[$key])) {
            $item = clone $this->deferred[$key];
            $item->moveTagsToPrevious();
            return $item;
        }
        $func = function () use ($key) {
            try {
                return $this->fetchObjectFromCache($key);
            } catch (\Exception $e) {
                $this->handleException($e, __FUNCTION__);
            }
        };
        return new CacheItem($key, $func);
    }
    
    public function getItems(array $keys = [])
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }
    
    public function hasItem($key)
    {
        try {
            return $this->getItem($key)->isHit();
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }
    
    public function clear()
    {
        $this->deferred = [];
        try {
            return $this->clearAllObjectsFromCache();
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }
    
    public function deleteItem($key)
    {
        try {
            return $this->deleteItems([$key]);
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }
    
    public function deleteItems(array $keys)
    {
        $deleted = \true;
        foreach ($keys as $key) {
            $this->validateKey($key);
            
            unset($this->deferred[$key]);
            
            $this->commit();
            $this->preRemoveItem($key);
            if (!$this->clearOneObjectFromCache($key)) {
                $deleted = \false;
            }
        }
        return $deleted;
    }
    
    public function save(CacheItemInterface $item)
    {
        if (!$item instanceof PhpCacheItem) {
            $e = new InvalidArgumentException('Cache items are not transferable between pools. Item MUST implement PhpCacheItem.');
            $this->handleException($e, __FUNCTION__);
        }
        $this->removeTagEntries($item);
        $this->saveTags($item);
        $timeToLive = null;
        if (null !== $timestamp = $item->getExpirationTimestamp()) {
            $timeToLive = $timestamp - time();
            if ($timeToLive < 0) {
                return $this->deleteItem($item->getKey());
            }
        }
        try {
            return $this->storeItemInCache($item, $timeToLive);
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }
    
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferred[$item->getKey()] = $item;
        return \true;
    }
    
    public function commit()
    {
        $saved = \true;
        foreach ($this->deferred as $item) {
            if (!$this->save($item)) {
                $saved = \false;
            }
        }
        $this->deferred = [];
        return $saved;
    }
    /**
     * @param string $key
     * @throws InvalidArgumentException
     */
    protected function validateKey($key)
    {
        if (!is_string($key)) {
            $e = new InvalidArgumentException(sprintf('Cache key must be string, "%s" given', gettype($key)));
            $this->handleException($e, __FUNCTION__);
        }
        if (!isset($key[0])) {
            $e = new InvalidArgumentException('Cache key cannot be an empty string');
            $this->handleException($e, __FUNCTION__);
        }
        if (preg_match('|[\{\}\(\)/\\\\@\:]|', $key)) {
            $e = new InvalidArgumentException(sprintf('Invalid key: "%s". The key contains one or more characters reserved for future extension: {}()/\@:', $key));
            $this->handleException($e, __FUNCTION__);
        }
    }
    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $message, $context);
        }
    }
    /**
     * @param \Exception $e
     * @param string $function
     * @throws CachePoolException
     */
    private function handleException(\Exception $e, $function)
    {
        $level = 'alert';
        if ($e instanceof InvalidArgumentException) {
            $level = 'warning';
        }
        $this->log($level, $e->getMessage(), ['exception' => $e]);
        if (!$e instanceof CacheException) {
            $e = new CachePoolException(sprintf('Exception thrown when executing "%s". ', $function), 0, $e);
        }
        throw $e;
    }
    /**
     * @param array $tags
     * @return bool
     */
    public function invalidateTags(array $tags)
    {
        $itemIds = [];
        foreach ($tags as $tag) {
            $itemIds = array_merge($itemIds, $this->getList($this->getTagKey($tag)));
        }
        
        $success = $this->deleteItems($itemIds);
        if ($success) {
            foreach ($tags as $tag) {
                $this->removeList($this->getTagKey($tag));
                $l = $this->getList($this->getTagKey($tag));
            }
        }
        return $success;
    }
    public function invalidateTag($tag)
    {
        return $this->invalidateTags([$tag]);
    }
    /**
     * @param PhpCacheItem $item
     */
    protected function saveTags(PhpCacheItem $item)
    {
        $tags = $item->getTags();
        foreach ($tags as $tag) {
            $this->appendListItem($this->getTagKey($tag), $item->getKey());
        }
    }
    /**
     * @param string $key
     * @return $this
     */
    protected function preRemoveItem($key)
    {
        $item = $this->getItem($key);
        $this->removeTagEntries($item);
        return $this;
    }
    /**
     * @param PhpCacheItem $item
     */
    private function removeTagEntries(PhpCacheItem $item)
    {
        $tags = $item->getPreviousTags();
        foreach ($tags as $tag) {
            $this->removeListItem($this->getTagKey($tag), $item->getKey());
        }
    }
    /**
     * @param string $tag
     * @return string
     */
    protected function getTagKey($tag)
    {
        return 'tag' . self::SEPARATOR_TAG . $tag;
    }
    
    public function get($key, $default = null)
    {
        $item = $this->getItem($key);
        if (!$item->isHit()) {
            return $default;
        }
        return $item->get();
    }
    
    public function set($key, $value, $ttl = null)
    {
        $item = $this->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);
        return $this->save($item);
    }
    
    public function delete($key)
    {
        return $this->deleteItem($key);
    }
    
    public function getMultiple($keys, $default = null)
    {
        if (!is_array($keys)) {
            if (!$keys instanceof \Traversable) {
                throw new InvalidArgumentException('$keys is neither an array nor Traversable');
            }

            $keys = iterator_to_array($keys, \false);
        }
        $items = $this->getItems($keys);
        return $this->generateValues($default, $items);
    }
    /**
     * @return \Generator
     */
    private function generateValues($default, $items)
    {
        foreach ($items as $key => $item) {
            if (!$item->isHit()) {
                yield $key => $default;
            } else {
                yield $key => $item->get();
            }
        }
    }
    
    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values)) {
            if (!$values instanceof \Traversable) {
                throw new InvalidArgumentException('$values is neither an array nor Traversable');
            }
        }
        $keys = [];
        $arrayValues = [];
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $key = (string) $key;
            }
            $this->validateKey($key);
            $keys[] = $key;
            $arrayValues[$key] = $value;
        }
        $items = $this->getItems($keys);
        $itemSuccess = \true;
        foreach ($items as $key => $item) {
            $item->set($arrayValues[$key]);
            try {
                $item->expiresAfter($ttl);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }
            $itemSuccess = $itemSuccess && $this->saveDeferred($item);
        }
        return $itemSuccess && $this->commit();
    }
    
    public function deleteMultiple($keys)
    {
        if (!is_array($keys)) {
            if (!$keys instanceof \Traversable) {
                throw new InvalidArgumentException('$keys is neither an array nor Traversable');
            }

            $keys = iterator_to_array($keys, \false);
        }
        return $this->deleteItems($keys);
    }
    
    public function has($key)
    {
        return $this->hasItem($key);
    }
}
