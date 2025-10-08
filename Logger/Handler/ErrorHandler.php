<?php

namespace Comfino\ComfinoGateway\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;

class ErrorHandler extends BaseHandler
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::ERROR;

    /**
     * @var string
     */
    protected $fileName = '/var/log/comfino_payment_error.log';

    /**
     * Write log record
     *
     * @param array|LogRecord $record
     * @return void
     */
    public function write($record): void
    {
        // Handle both Monolog 2 (array) and Monolog 3 (LogRecord object).
        $level = is_array($record) ? $record['level'] : $record->level->value;

        if ($level === $this->loggerType) {
            parent::write($record);
        }
    }
}
