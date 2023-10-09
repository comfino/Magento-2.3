<?php

namespace Comfino\ComfinoGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\DirectoryList;

class ErrorLog extends AbstractHelper
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    public function __construct(Context $context, DirectoryList $directoryList)
    {
        parent::__construct($context);

        $this->directoryList = $directoryList;
    }

    public function getErrorLog($numLines): string
    {
        $errorsLog = '';
        $logFilePath = $this->directoryList->getPath('log') . '/comfino_payment_error.log';

        if (file_exists($logFilePath)) {
            $file = new \SplFileObject($logFilePath, 'r');
            $file->seek(PHP_INT_MAX);

            $lastLine = $file->key();

            $lines = new \LimitIterator(
                $file,
                $lastLine > $numLines ? $lastLine - $numLines : 0,
                $lastLine ?: 1
            );

            $errorsLog = implode('', iterator_to_array($lines));
        }

        return $errorsLog;
    }
}
