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

use ComfinoExternal\Cache\Adapter\Common\Exception\InvalidArgumentException;
use ComfinoExternal\Cache\TagInterop\TaggableCacheItemInterface;

class CacheItem implements PhpCacheItem
{
    /**
     * @type array
     */
    private $prevTags = [];
    /**
     * @type array
     */
    private $tags = [];
    /**
     * @type \Closure
     */
    private $callable;
    /**
     * @type string
     */
    private $key;
    /**
     * @type mixed
     */
    private $value;
    /**
     * @type int|null
     */
    private $expirationTimestamp = null;
    /**
     * @type bool
     */
    private $hasValue = \false;
    /**
     * @param string $key
     * @param \Closure|bool $callable
     */
    public function __construct($key, $callable = null, $value = null)
    {
        $this->key = $key;
        if ($callable === \true) {
            $this->hasValue = \true;
            $this->value = $value;
        } elseif ($callable !== \false) {
            $this->callable = $callable;
        }
    }
    
    public function getKey()
    {
        return $this->key;
    }
    
    public function set($value)
    {
        $this->value = $value;
        $this->hasValue = \true;
        $this->callable = null;
        return $this;
    }
    
    public function get()
    {
        if (!$this->isHit()) {
            return;
        }
        return $this->value;
    }
    
    public function isHit()
    {
        $this->initialize();
        if (!$this->hasValue) {
            return \false;
        }
        if ($this->expirationTimestamp !== null) {
            return $this->expirationTimestamp > time();
        }
        return \true;
    }
    
    public function getExpirationTimestamp()
    {
        return $this->expirationTimestamp;
    }
    
    public function expiresAt($expiration)
    {
        if ($expiration instanceof \DateTimeInterface) {
            $this->expirationTimestamp = $expiration->getTimestamp();
        } elseif (is_int($expiration) || null === $expiration) {
            $this->expirationTimestamp = $expiration;
        } else {
            throw new InvalidArgumentException('Cache item ttl/expiresAt must be of type integer or \DateTimeInterface.');
        }
        return $this;
    }
    
    public function expiresAfter($time)
    {
        if ($time === null) {
            $this->expirationTimestamp = null;
        } elseif ($time instanceof \DateInterval) {
            $date = new \DateTime();
            $date->add($time);
            $this->expirationTimestamp = $date->getTimestamp();
        } elseif (is_int($time)) {
            $this->expirationTimestamp = time() + $time;
        } else {
            throw new InvalidArgumentException('Cache item ttl/expiresAfter must be of type integer or \DateInterval.');
        }
        return $this;
    }
    
    public function getPreviousTags()
    {
        $this->initialize();
        return $this->prevTags;
    }
    
    public function getTags()
    {
        return $this->tags;
    }
    
    public function setTags(array $tags)
    {
        $this->tags = [];
        $this->tag($tags);
        return $this;
    }
    /**
     * @param string|string[] $tags
     * @throws InvalidArgumentException
     * @return TaggableCacheItemInterface
     */
    private function tag($tags)
    {
        $this->initialize();
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                throw new InvalidArgumentException(sprintf('Cache tag must be string, "%s" given', is_object($tag) ? get_class($tag) : gettype($tag)));
            }
            if (isset($this->tags[$tag])) {
                continue;
            }
            if (!isset($tag[0])) {
                throw new InvalidArgumentException('Cache tag length must be greater than zero');
            }
            if (isset($tag[strcspn($tag, '{}()/\@:')])) {
                throw new InvalidArgumentException(sprintf('Cache tag "%s" contains reserved characters {}()/\@:', $tag));
            }
            $this->tags[$tag] = $tag;
        }
        return $this;
    }
    
    private function initialize()
    {
        if ($this->callable !== null) {
            $func = $this->callable;
            $result = $func();
            $this->hasValue = $result[0];
            $this->value = $result[1];
            $this->prevTags = isset($result[2]) ? $result[2] : [];
            $this->expirationTimestamp = null;
            if (isset($result[3]) && is_int($result[3])) {
                $this->expirationTimestamp = $result[3];
            }
            $this->callable = null;
        }
    }
    
    public function moveTagsToPrevious()
    {
        $this->prevTags = $this->tags;
        $this->tags = [];
    }
}
