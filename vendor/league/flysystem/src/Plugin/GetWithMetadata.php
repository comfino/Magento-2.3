<?php

namespace ComfinoExternal\League\Flysystem\Plugin;

use InvalidArgumentException;
use ComfinoExternal\League\Flysystem\FileNotFoundException;
class GetWithMetadata extends AbstractPlugin
{
    /**
     * @return string
     */
    public function getMethod()
    {
        return 'getWithMetadata';
    }
    /**
     * @param string $path
     * @param array $metadata
     * @throws InvalidArgumentException
     * @throws FileNotFoundException
     * @return array|false
     */
    public function handle($path, array $metadata)
    {
        $object = $this->filesystem->getMetadata($path);
        if (!$object) {
            return \false;
        }
        $keys = array_diff($metadata, array_keys($object));
        foreach ($keys as $key) {
            if (!method_exists($this->filesystem, $method = 'get' . ucfirst($key))) {
                throw new InvalidArgumentException('Could not fetch metadata: ' . $key);
            }
            $object[$key] = $this->filesystem->{$method}($path);
        }
        return $object;
    }
}
