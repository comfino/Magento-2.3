<?php

namespace ComfinoExternal\League\Flysystem;

interface AdapterInterface extends ReadInterface
{
    const VISIBILITY_PUBLIC = 'public';
    
    const VISIBILITY_PRIVATE = 'private';
    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     * @return array|false
     */
    public function write($path, $contents, Config $config);
    /**
     * @param string $path
     * @param resource $resource
     * @param Config $config
     * @return array|false
     */
    public function writeStream($path, $resource, Config $config);
    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     * @return array|false
     */
    public function update($path, $contents, Config $config);
    /**
     * @param string $path
     * @param resource $resource
     * @param Config $config
     * @return array|false
     */
    public function updateStream($path, $resource, Config $config);
    /**
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function rename($path, $newpath);
    /**
     * @param string $path
     * @param string $newpath
     * @return bool
     */
    public function copy($path, $newpath);
    /**
     * @param string $path
     * @return bool
     */
    public function delete($path);
    /**
     * @param string $dirname
     * @return bool
     */
    public function deleteDir($dirname);
    /**
     * @param string $dirname
     * @param Config $config
     * @return array|false
     */
    public function createDir($dirname, Config $config);
    /**
     * @param string $path
     * @param string $visibility
     * @return array|false
     */
    public function setVisibility($path, $visibility);
}
