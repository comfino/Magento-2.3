<?php

namespace ComfinoExternal\League\Flysystem\Plugin;

use ComfinoExternal\League\Flysystem\FileExistsException;
use ComfinoExternal\League\Flysystem\FileNotFoundException;
class ForcedCopy extends AbstractPlugin
{
    public function getMethod()
    {
        return 'forceCopy';
    }
    /**
     * @param string $path
     * @param string $newpath
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @return bool
     */
    public function handle($path, $newpath)
    {
        try {
            $deleted = $this->filesystem->delete($newpath);
        } catch (FileNotFoundException $e) {
            $deleted = \true;
        }
        if ($deleted) {
            return $this->filesystem->copy($path, $newpath);
        }
        return \false;
    }
}
