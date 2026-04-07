<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace ComfinoExternal\Cache\Hierarchy;

use ComfinoExternal\Cache\Adapter\Common\AbstractCachePool;

trait HierarchicalCachePoolTrait
{
    /**
     * @type array
     */
    private $keyCache = [];
    /**
     * @param string $name
     * @return mixed
     */
    abstract public function getDirectValue($name);
    /**
     * @param string $key
     * @return string|array
     */
    protected function getHierarchyKey($key, &$pathKey = null)
    {
        if (!$this->isHierarchyKey($key)) {
            return $key;
        }
        $key = $this->explodeKey($key);
        $keyString = '';
        
        foreach ($key as $name) {
            $keyString .= $name;
            $pathKey = sha1('path' . AbstractCachePool::SEPARATOR_TAG . $keyString);
            if (isset($this->keyCache[$pathKey])) {
                $index = $this->keyCache[$pathKey];
            } else {
                $index = $this->getDirectValue($pathKey);
                $this->keyCache[$pathKey] = $index;
            }

            $keyString .= AbstractCachePool::SEPARATOR_TAG . $index . AbstractCachePool::SEPARATOR_TAG;
        }

        return sha1($keyString);
    }
    
    protected function clearHierarchyKeyCache()
    {
        $this->keyCache = [];
    }
    /**
     * @param string $key
     * @return bool
     */
    private function isHierarchyKey($key)
    {
        return substr($key, 0, 1) === HierarchicalPoolInterface::HIERARCHY_SEPARATOR;
    }
    /**
     * @param string $string
     * @return array
     */
    private function explodeKey($string)
    {
        list($key, $tag) = explode(AbstractCachePool::SEPARATOR_TAG, $string . AbstractCachePool::SEPARATOR_TAG);
        if ($key === HierarchicalPoolInterface::HIERARCHY_SEPARATOR) {
            $parts = ['root'];
        } else {
            $parts = explode(HierarchicalPoolInterface::HIERARCHY_SEPARATOR, $key);
            
            $parts[0] = 'root';
        }
        return array_map(function ($level) use ($tag) {
            return $level . AbstractCachePool::SEPARATOR_TAG . $tag;
        }, $parts);
    }
}
