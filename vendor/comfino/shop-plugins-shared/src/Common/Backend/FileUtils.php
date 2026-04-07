<?php

declare(strict_types=1);

namespace Comfino\Common\Backend;

class FileUtils
{
    /**
     * @param string[] $components
     */
    public static function buildPathFromComponents($components): string
    {
        return implode(DIRECTORY_SEPARATOR, $components);
    }

    /**
     * @param string $filePath
     */
    public static function read($filePath): string
    {
        try {
            $file = new \SplFileObject($filePath, 'r');
        } catch (\Exception $exception) {
            return '';
        }

        if (!$file->isReadable()) {
            return '';
        }

        return $file->fread($file->getSize());
    }

    /**
     * @param string $filePath
     * @param int $numLines
     * @return string[]
     */
    public static function readLastLines($filePath, $numLines): array
    {
        try {
            $file = new \SplFileObject($filePath, 'r');
        } catch (\Exception $exception) {
            return [];
        }

        if (!$file->isReadable()) {
            return [];
        }

        $file->seek(PHP_INT_MAX);

        $lastLine = $file->key();

        return iterator_to_array(new \LimitIterator(
            $file,
            $lastLine > $numLines ? $lastLine - $numLines : 0,
            $lastLine ?: 1
        ));
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public static function write($filePath, $content): void
    {
        (new \SplFileObject($filePath, 'w'))->fwrite($content);
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public static function append($filePath, $content): void
    {
        (new \SplFileObject($filePath, 'a'))->fwrite($content);
    }

    /**
     * @param string $filePath
     */
    public static function exists($filePath): bool
    {
        return (new \SplFileInfo($filePath))->isFile();
    }

    /**
     * @param string $filePath
     */
    public static function isWritable($filePath): bool
    {
        return (new \SplFileInfo($filePath))->isWritable();
    }

    /**
     * @param string $filePath
     */
    public static function isReadable($filePath): bool
    {
        return (new \SplFileInfo($filePath))->isReadable();
    }
}
