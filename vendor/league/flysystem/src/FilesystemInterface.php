<?php

namespace ComfinoExternal\League\Flysystem;

use InvalidArgumentException;
interface FilesystemInterface
{
    /**
     * @param string $path
     * @return bool
     */
    public function has($path);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function read($path);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return resource|false
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
     * @throws FileNotFoundException
     * @return array|false
     */
    public function getMetadata($path);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return int|false
     */
    public function getSize($path);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function getMimetype($path);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return int|false
     */
    public function getTimestamp($path);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function getVisibility($path);
    /**
     * @param string $path
     * @param string $contents
     * @param array $config
     * @throws FileExistsException
     * @return bool
     */
    public function write($path, $contents, array $config = []);
    /**
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @throws InvalidArgumentException
     * @throws FileExistsException
     * @return bool
     */
    public function writeStream($path, $resource, array $config = []);
    /**
     * @param string $path
     * @param string $contents
     * @param array $config
     * @throws FileNotFoundException
     * @return bool
     */
    public function update($path, $contents, array $config = []);
    /**
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @throws InvalidArgumentException
     * @throws FileNotFoundException
     * @return bool
     */
    public function updateStream($path, $resource, array $config = []);
    /**
     * @param string $path
     * @param string $newpath
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @return bool
     */
    public function rename($path, $newpath);
    /**
     * @param string $path
     * @param string $newpath
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @return bool
     */
    public function copy($path, $newpath);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return bool
     */
    public function delete($path);
    /**
     * @param string $dirname
     * @throws RootViolationException
     * @return bool
     */
    public function deleteDir($dirname);
    /**
     * @param string $dirname
     * @param array $config
     * @return bool
     */
    public function createDir($dirname, array $config = []);
    /**
     * @param string $path
     * @param string $visibility
     * @throws FileNotFoundException
     * @return bool
     */
    public function setVisibility($path, $visibility);
    /**
     * @param string $path
     * @param string $contents
     * @param array $config
     * @return bool
     */
    public function put($path, $contents, array $config = []);
    /**
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @throws InvalidArgumentException
     * @return bool
     */
    public function putStream($path, $resource, array $config = []);
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function readAndDelete($path);
    /**
     * @param string $path
     * @param Handler $handler
     * @return Handler
     */
    public function get($path, Handler $handler = null);
    /**
     * @param PluginInterface $plugin
     * @return $this
     */
    public function addPlugin(PluginInterface $plugin);
}
