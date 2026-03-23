<?php

declare(strict_types=1);

namespace Comfino\Common\Backend;

abstract class Logger
{
    /**
     * @param string $logFilePath
     * @return string|null
     */
    protected function findActualLogFile($logFilePath): ?string
    {
        if (file_exists($logFilePath)) {
            return $logFilePath;
        }

        $dir = dirname($logFilePath);
        $filename = pathinfo($logFilePath, PATHINFO_FILENAME);
        $extension = pathinfo($logFilePath, PATHINFO_EXTENSION);

        $files = glob($dir . DIRECTORY_SEPARATOR . $filename . '-*.' . $extension);

        if (empty($files)) {
            return null;
        }

        usort($files, static function ($filename1, $filename2) {
            return filemtime($filename2) <=> filemtime($filename1);
        });

        return $files[0];
    }

    /**
     * @param string $logFilePath
     * @return string[]
     */
    protected function findAllLogFiles($logFilePath): array
    {
        $filename = pathinfo($logFilePath, PATHINFO_FILENAME);
        $extension = pathinfo($logFilePath, PATHINFO_EXTENSION);

        $files = glob(dirname($logFilePath) . DIRECTORY_SEPARATOR . $filename . '-*.' . $extension);

        return $files ?: [];
    }

    /**
     * @param string $logFilePath
     * @return int
     */
    protected function clearLogFiles($logFilePath): int
    {
        $deletedCount = 0;

        if (file_exists($logFilePath) && unlink($logFilePath)) {
            $deletedCount++;
        }

        foreach ($this->findAllLogFiles($logFilePath) as $file) {
            if (file_exists($file) && unlink($file)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
