<?php

namespace ComfinoExternal\League\Flysystem\Plugin;

class ListFiles extends AbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return 'listFiles';
    }
    /**
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function handle($directory = '', $recursive = \false)
    {
        $contents = $this->filesystem->listContents($directory, $recursive);
        $filter = function ($object) {
            return $object['type'] === 'file';
        };
        return array_values(array_filter($contents, $filter));
    }
}
