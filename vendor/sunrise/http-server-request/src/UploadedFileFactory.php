<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/http-server-request/blob/master/LICENSE
 * @link https://github.com/sunrise-php/http-server-request
 */
namespace ComfinoExternal\Sunrise\Http\ServerRequest;

use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Psr\Http\Message\UploadedFileFactoryInterface;
use ComfinoExternal\Psr\Http\Message\UploadedFileInterface;

use const UPLOAD_ERR_OK;

class UploadedFileFactory implements UploadedFileFactoryInterface
{
    public function createUploadedFile(StreamInterface $stream, ?int $size = null, int $error = UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null): UploadedFileInterface
    {
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }
}
