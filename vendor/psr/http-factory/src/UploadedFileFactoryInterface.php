<?php

namespace ComfinoExternal\Psr\Http\Message;

interface UploadedFileFactoryInterface
{
    /**
     * @param StreamInterface $stream
     * @param int|null $size
     * @param int $error
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     * @return UploadedFileInterface
     * @throws \InvalidArgumentException
     */
    public function createUploadedFile(StreamInterface $stream, ?int $size = null, int $error = \UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null): UploadedFileInterface;
}
