<?php

namespace ComfinoExternal\League\Flysystem;

use Exception as BaseException;
class FileExistsException extends Exception
{
    /**
     * @var string
     */
    protected $path;
    /**
     * @param string $path
     * @param int $code
     * @param BaseException $previous
     */
    public function __construct($path, $code = 0, BaseException $previous = null)
    {
        $this->path = $path;
        parent::__construct('File already exists at path: ' . $this->getPath(), $code, $previous);
    }
    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
