<?php

namespace ComfinoExternal\League\Flysystem\Plugin;

class ListPaths extends AbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return 'listPaths';
    }
    /**
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function handle($directory = '', $recursive = \false)
    {
        $result = [];
        $contents = $this->filesystem->listContents($directory, $recursive);
        foreach ($contents as $object) {
            $result[] = $object['path'];
        }
        return $result;
    }
}
