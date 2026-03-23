<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace ComfinoExternal\Cache\Adapter\Filesystem;

use ComfinoExternal\Cache\Adapter\Common\AbstractCachePool;
use ComfinoExternal\Cache\Adapter\Common\Exception\InvalidArgumentException;
use ComfinoExternal\Cache\Adapter\Common\PhpCacheItem;
use ComfinoExternal\League\Flysystem\FileExistsException;
use ComfinoExternal\League\Flysystem\FileNotFoundException;
use ComfinoExternal\League\Flysystem\FilesystemInterface;

class FilesystemCachePool extends AbstractCachePool
{
    /**
     * @type FilesystemInterface
     */
    private $filesystem;
    /**
     * @type string
     */
    private $folder;
    /**
     * @param FilesystemInterface $filesystem
     * @param string $folder
     */
    public function __construct(FilesystemInterface $filesystem, $folder = 'cache')
    {
        $this->folder = $folder;
        $this->filesystem = $filesystem;
        $this->filesystem->createDir($this->folder);
    }
    /**
     * @param string $folder
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
    }
    
    protected function fetchObjectFromCache($key)
    {
        $empty = [\false, null, [], null];
        $file = $this->getFilePath($key);
        try {
            $data = @unserialize($this->filesystem->read($file));
            if ($data === \false) {
                return $empty;
            }
        } catch (FileNotFoundException $e) {
            return $empty;
        }
        
        $expirationTimestamp = $data[2] ?: null;
        if ($expirationTimestamp !== null && time() > $expirationTimestamp) {
            foreach ($data[1] as $tag) {
                $this->removeListItem($this->getTagKey($tag), $key);
            }
            $this->forceClear($key);
            return $empty;
        }
        return [\true, $data[0], $data[1], $expirationTimestamp];
    }
    
    protected function clearAllObjectsFromCache()
    {
        $this->filesystem->deleteDir($this->folder);
        $this->filesystem->createDir($this->folder);
        return \true;
    }
    
    protected function clearOneObjectFromCache($key)
    {
        return $this->forceClear($key);
    }
    
    protected function storeItemInCache(PhpCacheItem $item, $ttl)
    {
        $data = serialize([$item->get(), $item->getTags(), $item->getExpirationTimestamp()]);
        $file = $this->getFilePath($item->getKey());
        if ($this->filesystem->has($file)) {
            return $this->filesystem->update($file, $data);
        }
        try {
            return $this->filesystem->write($file, $data);
        } catch (FileExistsException $e) {
            return $this->filesystem->update($file, $data);
        }
    }
    /**
     * @param string $key
     * @throws InvalidArgumentException
     * @return string
     */
    private function getFilePath($key)
    {
        if (!preg_match('|^[a-zA-Z0-9_\.! ]+$|', $key)) {
            throw new InvalidArgumentException(sprintf('Invalid key "%s". Valid filenames must match [a-zA-Z0-9_\.! ].', $key));
        }
        return sprintf('%s/%s', $this->folder, $key);
    }
    
    protected function getList($name)
    {
        $file = $this->getFilePath($name);
        if (!$this->filesystem->has($file)) {
            $this->filesystem->write($file, serialize([]));
        }
        return unserialize($this->filesystem->read($file));
    }
    
    protected function removeList($name)
    {
        $file = $this->getFilePath($name);
        $this->filesystem->delete($file);
    }
    
    protected function appendListItem($name, $key)
    {
        $list = $this->getList($name);
        $list[] = $key;
        return $this->filesystem->update($this->getFilePath($name), serialize($list));
    }
    
    protected function removeListItem($name, $key)
    {
        $list = $this->getList($name);
        foreach ($list as $i => $item) {
            if ($item === $key) {
                unset($list[$i]);
            }
        }
        return $this->filesystem->update($this->getFilePath($name), serialize($list));
    }
    /**
     * @return bool
     */
    private function forceClear($key)
    {
        try {
            return $this->filesystem->delete($this->getFilePath($key));
        } catch (FileNotFoundException $e) {
            return \true;
        }
    }
}
