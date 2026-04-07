<?php

namespace ComfinoExternal\League\Flysystem\Plugin;

class EmptyDir extends AbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return 'emptyDir';
    }
    /**
     * @param string $dirname
     */
    public function handle($dirname)
    {
        $listing = $this->filesystem->listContents($dirname, \false);
        foreach ($listing as $item) {
            if ($item['type'] === 'dir') {
                $this->filesystem->deleteDir($item['path']);
            } else {
                $this->filesystem->delete($item['path']);
            }
        }
    }
}
