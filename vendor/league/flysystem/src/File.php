<?php

namespace ComfinoExternal\League\Flysystem;

class File extends Handler
{
    /**
     * @return bool
     */
    public function exists()
    {
        return $this->filesystem->has($this->path);
    }
    /**
     * @return string|false
     */
    public function read()
    {
        return $this->filesystem->read($this->path);
    }
    /**
     * @return resource|false
     */
    public function readStream()
    {
        return $this->filesystem->readStream($this->path);
    }
    /**
     * @param string $content
     * @return bool
     */
    public function write($content)
    {
        return $this->filesystem->write($this->path, $content);
    }
    /**
     * @param resource $resource
     * @return bool
     */
    public function writeStream($resource)
    {
        return $this->filesystem->writeStream($this->path, $resource);
    }
    /**
     * @param string $content
     * @return bool
     */
    public function update($content)
    {
        return $this->filesystem->update($this->path, $content);
    }
    /**
     * @param resource $resource
     * @return bool
     */
    public function updateStream($resource)
    {
        return $this->filesystem->updateStream($this->path, $resource);
    }
    /**
     * @param string $content
     * @return bool
     */
    public function put($content)
    {
        return $this->filesystem->put($this->path, $content);
    }
    /**
     * @param resource $resource
     * @return bool
     */
    public function putStream($resource)
    {
        return $this->filesystem->putStream($this->path, $resource);
    }
    /**
     * @param string $newpath
     * @return bool
     */
    public function rename($newpath)
    {
        if ($this->filesystem->rename($this->path, $newpath)) {
            $this->path = $newpath;
            return \true;
        }
        return \false;
    }
    /**
     * @param string $newpath
     * @return File|false
     */
    public function copy($newpath)
    {
        if ($this->filesystem->copy($this->path, $newpath)) {
            return new File($this->filesystem, $newpath);
        }
        return \false;
    }
    /**
     * @return string|false
     */
    public function getTimestamp()
    {
        return $this->filesystem->getTimestamp($this->path);
    }
    /**
     * @return string|false
     */
    public function getMimetype()
    {
        return $this->filesystem->getMimetype($this->path);
    }
    /**
     * @return string|false
     */
    public function getVisibility()
    {
        return $this->filesystem->getVisibility($this->path);
    }
    /**
     * @return array|false
     */
    public function getMetadata()
    {
        return $this->filesystem->getMetadata($this->path);
    }
    /**
     * @return int|false
     */
    public function getSize()
    {
        return $this->filesystem->getSize($this->path);
    }
    /**
     * @return bool
     */
    public function delete()
    {
        return $this->filesystem->delete($this->path);
    }
}
