<?php

namespace ComfinoExternal\League\Flysystem;

interface ReadInterface
{
    /**
     * @param string $path
     * @return array|bool|null
     */
    public function has($path);
    /**
     * @param string $path
     * @return array|false
     */
    public function read($path);
    /**
     * @param string $path
     * @return array|false
     */
    public function readStream($path);
    /**
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function listContents($directory = '', $recursive = \false);
    /**
     * @param string $path
     * @return array|false
     */
    public function getMetadata($path);
    /**
     * @param string $path
     * @return array|false
     */
    public function getSize($path);
    /**
     * @param string $path
     * @return array|false
     */
    public function getMimetype($path);
    /**
     * @param string $path
     * @return array|false
     */
    public function getTimestamp($path);
    /**
     * @param string $path
     * @return array|false
     */
    public function getVisibility($path);
}
