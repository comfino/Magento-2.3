<?php

namespace ComfinoExternal\League\Flysystem;

interface PluginInterface
{
    /**
     * @return string
     */
    public function getMethod();
    /**
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem);
}
