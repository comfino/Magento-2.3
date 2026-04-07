<?php

namespace ComfinoExternal\League\Flysystem\Adapter;

use ComfinoExternal\League\Flysystem\AdapterInterface;
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var string|null
     */
    protected $pathPrefix;
    /**
     * @var string
     */
    protected $pathSeparator = '/';
    /**
     * @param string $prefix
     * @return void
     */
    public function setPathPrefix($prefix)
    {
        $prefix = (string) $prefix;
        if ($prefix === '') {
            $this->pathPrefix = null;
            return;
        }
        $this->pathPrefix = rtrim($prefix, '\/') . $this->pathSeparator;
    }
    /**
     * @return string|null
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }
    /**
     * @param string $path
     * @return string
     */
    public function applyPathPrefix($path)
    {
        return $this->getPathPrefix() . ltrim($path, '\/');
    }
    /**
     * @param string $path
     * @return string
     */
    public function removePathPrefix($path)
    {
        return substr($path, strlen($this->getPathPrefix()));
    }
}
