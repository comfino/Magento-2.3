<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace ComfinoExternal\Cache\TagInterop;

use ComfinoExternal\Psr\Cache\CacheItemPoolInterface;
use ComfinoExternal\Psr\Cache\InvalidArgumentException;

interface TaggableCacheItemPoolInterface extends CacheItemPoolInterface
{
    /**
     * @param string $tag
     * @throws InvalidArgumentException
     * @return bool
     */
    public function invalidateTag($tag);
    /**
     * @param string[] $tags
     * @throws InvalidArgumentException
     * @return bool
     */
    public function invalidateTags(array $tags);
    /**
     * @return TaggableCacheItemInterface
     */
    public function getItem($key);
    /**
     * @return array|\Traversable|TaggableCacheItemInterface[]
     */
    public function getItems(array $keys = []);
}
