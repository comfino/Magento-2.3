<?php

namespace ComfinoExternal\League\Flysystem\Adapter\Polyfill;

trait StreamedReadingTrait
{
    /**
     * @param string $path
     * @return array|false
     */
    public function readStream($path)
    {
        if (!$data = $this->read($path)) {
            return \false;
        }
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $data['contents']);
        rewind($stream);
        $data['stream'] = $stream;
        unset($data['contents']);
        return $data;
    }
    /**
     * @param string $path
     * @return array|false
     */
    abstract public function read($path);
}
