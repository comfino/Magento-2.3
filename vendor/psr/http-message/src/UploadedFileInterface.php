<?php

namespace ComfinoExternal\Psr\Http\Message;

interface UploadedFileInterface
{
    /**
     * @return StreamInterface
     * @throws \RuntimeException
     */
    public function getStream();
    /**
     * @param string $targetPath
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function moveTo($targetPath);
    /**
     * @return int|null
     */
    public function getSize();
    /**
     * @return int
     */
    public function getError();
    /**
     * @return string|null
     */
    public function getClientFilename();
    /**
     * @return string|null
     */
    public function getClientMediaType();
}
