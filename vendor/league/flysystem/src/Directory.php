<?php

namespace ComfinoExternal\League\Flysystem;

class Directory extends Handler
{
    /**
     * @return bool
     */
    public function delete()
    {
        return $this->filesystem->deleteDir($this->path);
    }
    /**
     * @param bool $recursive
     * @return array|bool
     */
    public function getContents($recursive = \false)
    {
        return $this->filesystem->listContents($this->path, $recursive);
    }
}
