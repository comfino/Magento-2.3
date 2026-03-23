<?php

namespace ComfinoExternal\League\Flysystem;

use InvalidArgumentException;
use ComfinoExternal\League\Flysystem\Plugin\PluggableTrait;
use ComfinoExternal\League\Flysystem\Plugin\PluginNotFoundException;
/**
     * @method AdapterInterface getAdapter($prefix)
     * @method Config getConfig($prefix)
     * @method array listFiles($directory = '', $recursive = false)
     * @method array listPaths($directory = '', $recursive = false)
     * @method array getWithMetadata($path, array $metadata)
     * @method Filesystem flushCache()
     * @method void assertPresent($path)
     * @method void assertAbsent($path)
     * @method Filesystem addPlugin(PluginInterface $plugin)
     */
class MountManager implements FilesystemInterface
{
    use PluggableTrait;
    /**
     * @var FilesystemInterface[]
     */
    protected $filesystems = [];
    /**
     * @param FilesystemInterface[] $filesystems
     * @throws InvalidArgumentException
     */
    public function __construct(array $filesystems = [])
    {
        $this->mountFilesystems($filesystems);
    }
    /**
     * @param FilesystemInterface[] $filesystems
     * @throws InvalidArgumentException
     * @return $this
     */
    public function mountFilesystems(array $filesystems)
    {
        foreach ($filesystems as $prefix => $filesystem) {
            $this->mountFilesystem($prefix, $filesystem);
        }
        return $this;
    }
    /**
     * @param string $prefix
     * @param FilesystemInterface $filesystem
     * @throws InvalidArgumentException
     * @return $this
     */
    public function mountFilesystem($prefix, FilesystemInterface $filesystem)
    {
        if (!is_string($prefix)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects argument #1 to be a string.');
        }
        $this->filesystems[$prefix] = $filesystem;
        return $this;
    }
    /**
     * @param string $prefix
     * @throws FilesystemNotFoundException
     * @return FilesystemInterface
     */
    public function getFilesystem($prefix)
    {
        if (!isset($this->filesystems[$prefix])) {
            throw new FilesystemNotFoundException('No filesystem mounted with prefix ' . $prefix);
        }
        return $this->filesystems[$prefix];
    }
    /**
     * @param array $arguments
     * @throws InvalidArgumentException
     * @return array
     */
    public function filterPrefix(array $arguments)
    {
        if (empty($arguments)) {
            throw new InvalidArgumentException('At least one argument needed');
        }
        $path = array_shift($arguments);
        if (!is_string($path)) {
            throw new InvalidArgumentException('First argument should be a string');
        }
        list($prefix, $path) = $this->getPrefixAndPath($path);
        array_unshift($arguments, $path);
        return [$prefix, $arguments];
    }
    /**
     * @param string $directory
     * @param bool $recursive
     * @throws InvalidArgumentException
     * @throws FilesystemNotFoundException
     * @return array
     */
    public function listContents($directory = '', $recursive = \false)
    {
        list($prefix, $directory) = $this->getPrefixAndPath($directory);
        $filesystem = $this->getFilesystem($prefix);
        $result = $filesystem->listContents($directory, $recursive);
        foreach ($result as &$file) {
            $file['filesystem'] = $prefix;
        }
        return $result;
    }
    /**
     * @param string $method
     * @param array $arguments
     * @throws InvalidArgumentException
     * @throws FilesystemNotFoundException
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        list($prefix, $arguments) = $this->filterPrefix($arguments);
        return $this->invokePluginOnFilesystem($method, $arguments, $prefix);
    }
    /**
     * @param string $from
     * @param string $to
     * @param array $config
     * @throws InvalidArgumentException
     * @throws FilesystemNotFoundException
     * @throws FileExistsException
     * @return bool
     */
    public function copy($from, $to, array $config = [])
    {
        list($prefixFrom, $from) = $this->getPrefixAndPath($from);
        $buffer = $this->getFilesystem($prefixFrom)->readStream($from);
        if ($buffer === \false) {
            return \false;
        }
        list($prefixTo, $to) = $this->getPrefixAndPath($to);
        $result = $this->getFilesystem($prefixTo)->writeStream($to, $buffer, $config);
        if (is_resource($buffer)) {
            fclose($buffer);
        }
        return $result;
    }
    /**
     * @param array $keys
     * @param string $directory
     * @param bool $recursive
     * @throws InvalidArgumentException
     * @throws FilesystemNotFoundException
     * @return array
     */
    public function listWith(array $keys = [], $directory = '', $recursive = \false)
    {
        list($prefix, $directory) = $this->getPrefixAndPath($directory);
        $arguments = [$keys, $directory, $recursive];
        return $this->invokePluginOnFilesystem('listWith', $arguments, $prefix);
    }
    /**
     * @param string $from
     * @param string $to
     * @param array $config
     * @throws InvalidArgumentException
     * @throws FilesystemNotFoundException
     * @return bool
     */
    public function move($from, $to, array $config = [])
    {
        list($prefixFrom, $pathFrom) = $this->getPrefixAndPath($from);
        list($prefixTo, $pathTo) = $this->getPrefixAndPath($to);
        if ($prefixFrom === $prefixTo) {
            $filesystem = $this->getFilesystem($prefixFrom);
            $renamed = $filesystem->rename($pathFrom, $pathTo);
            if ($renamed && isset($config['visibility'])) {
                return $filesystem->setVisibility($pathTo, $config['visibility']);
            }
            return $renamed;
        }
        $copied = $this->copy($from, $to, $config);
        if ($copied) {
            return $this->delete($from);
        }
        return \false;
    }
    /**
     * @param string $method
     * @param array $arguments
     * @param string $prefix
     * @throws FilesystemNotFoundException
     * @return mixed
     */
    public function invokePluginOnFilesystem($method, $arguments, $prefix)
    {
        $filesystem = $this->getFilesystem($prefix);
        try {
            return $this->invokePlugin($method, $arguments, $filesystem);
        } catch (PluginNotFoundException $e) {
        }
        $callback = [$filesystem, $method];
        return call_user_func_array($callback, $arguments);
    }
    /**
     * @param string $path
     * @throws InvalidArgumentException
     * @return string[]
     */
    protected function getPrefixAndPath($path)
    {
        if (strpos($path, '://') < 1) {
            throw new InvalidArgumentException('No prefix detected in path: ' . $path);
        }
        return explode('://', $path, 2);
    }
    /**
     * @param string $path
     * @return bool
     */
    public function has($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->has($path);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function read($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->read($path);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return resource|false
     */
    public function readStream($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->readStream($path);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return array|false
     */
    public function getMetadata($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->getMetadata($path);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return int|false
     */
    public function getSize($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->getSize($path);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function getMimetype($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->getMimetype($path);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function getTimestamp($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->getTimestamp($path);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function getVisibility($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->getVisibility($path);
    }
    /**
     * @param string $path
     * @param string $contents
     * @param array $config
     * @throws FileExistsException
     * @return bool
     */
    public function write($path, $contents, array $config = [])
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->write($path, $contents, $config);
    }
    /**
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @throws InvalidArgumentException
     * @throws FileExistsException
     * @return bool
     */
    public function writeStream($path, $resource, array $config = [])
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->writeStream($path, $resource, $config);
    }
    /**
     * @param string $path
     * @param string $contents
     * @param array $config
     * @throws FileNotFoundException
     * @return bool
     */
    public function update($path, $contents, array $config = [])
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->update($path, $contents, $config);
    }
    /**
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @throws InvalidArgumentException
     * @throws FileNotFoundException
     * @return bool
     */
    public function updateStream($path, $resource, array $config = [])
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->updateStream($path, $resource, $config);
    }
    /**
     * @param string $path
     * @param string $newpath
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @return bool
     */
    public function rename($path, $newpath)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->rename($path, $newpath);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return bool
     */
    public function delete($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->delete($path);
    }
    /**
     * @param string $dirname
     * @throws RootViolationException
     * @return bool
     */
    public function deleteDir($dirname)
    {
        list($prefix, $dirname) = $this->getPrefixAndPath($dirname);
        return $this->getFilesystem($prefix)->deleteDir($dirname);
    }
    /**
     * @param string $dirname
     * @param array $config
     * @return bool
     */
    public function createDir($dirname, array $config = [])
    {
        list($prefix, $dirname) = $this->getPrefixAndPath($dirname);
        return $this->getFilesystem($prefix)->createDir($dirname);
    }
    /**
     * @param string $path
     * @param string $visibility
     * @throws FileNotFoundException
     * @return bool
     */
    public function setVisibility($path, $visibility)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->setVisibility($path, $visibility);
    }
    /**
     * @param string $path
     * @param string $contents
     * @param array $config
     * @return bool
     */
    public function put($path, $contents, array $config = [])
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->put($path, $contents, $config);
    }
    /**
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @throws InvalidArgumentException
     * @return bool
     */
    public function putStream($path, $resource, array $config = [])
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->putStream($path, $resource, $config);
    }
    /**
     * @param string $path
     * @throws FileNotFoundException
     * @return string|false
     */
    public function readAndDelete($path)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->readAndDelete($path);
    }
    /**
     * @param string $path
     * @param Handler $handler
     * @return Handler
     */
    public function get($path, Handler $handler = null)
    {
        list($prefix, $path) = $this->getPrefixAndPath($path);
        return $this->getFilesystem($prefix)->get($path);
    }
}
