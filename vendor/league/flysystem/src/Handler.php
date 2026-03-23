<?php

namespace ComfinoExternal\League\Flysystem;

use BadMethodCallException;

abstract class Handler
{
    /**
     * @var string
     */
    protected $path;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;
    /**
     * @param FilesystemInterface $filesystem
     * @param string $path
     */
    public function __construct(FilesystemInterface $filesystem = null, $path = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }
    /**
     * @return bool
     */
    public function isDir()
    {
        return $this->getType() === 'dir';
    }
    /**
     * @return bool
     */
    public function isFile()
    {
        return $this->getType() === 'file';
    }
    /**
     * @return string
     */
    public function getType()
    {
        $metadata = $this->filesystem->getMetadata($this->path);
        return $metadata ? $metadata['type'] : 'dir';
    }
    /**
     * @param FilesystemInterface $filesystem
     * @return $this
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
        return $this;
    }
    /**
     * @return FilesystemInterface
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
    /**
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        array_unshift($arguments, $this->path);
        $callback = [$this->filesystem, $method];
        try {
            return call_user_func_array($callback, $arguments);
        } catch (BadMethodCallException $e) {
            throw new BadMethodCallException('Call to undefined method ' . get_called_class() . '::' . $method);
        }
    }
}
