<?php

namespace ComfinoExternal\League\Flysystem\Adapter\Polyfill;

use ComfinoExternal\League\Flysystem\Config;
use ComfinoExternal\League\Flysystem\Util;
trait StreamedWritingTrait
{
    /**
     * @param string $path
     * @param resource $resource
     * @param Config $config
     * @param string $fallback
     * @return mixed
     */
    protected function stream($path, $resource, Config $config, $fallback)
    {
        Util::rewindStream($resource);
        $contents = stream_get_contents($resource);
        $fallbackCall = [$this, $fallback];
        return call_user_func($fallbackCall, $path, $contents, $config);
    }
    /**
     * @param string $path
     * @param resource $resource
     * @param Config $config
     * @return mixed
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->stream($path, $resource, $config, 'write');
    }
    /**
     * @param string $path
     * @param resource $resource
     * @param Config $config
     * @return mixed
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->stream($path, $resource, $config, 'update');
    }
    
    abstract public function write($pash, $contents, Config $config);
    abstract public function update($pash, $contents, Config $config);
}
