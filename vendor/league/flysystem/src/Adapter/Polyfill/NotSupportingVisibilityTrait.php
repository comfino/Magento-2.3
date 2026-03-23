<?php

namespace ComfinoExternal\League\Flysystem\Adapter\Polyfill;

use LogicException;
trait NotSupportingVisibilityTrait
{
    /**
     * @param string $path
     * @throws LogicException
     */
    public function getVisibility($path)
    {
        throw new LogicException(get_class($this) . ' does not support visibility. Path: ' . $path);
    }
    /**
     * @param string $path
     * @param string $visibility
     * @throws LogicException
     */
    public function setVisibility($path, $visibility)
    {
        throw new LogicException(get_class($this) . ' does not support visibility. Path: ' . $path . ', visibility: ' . $visibility);
    }
}
