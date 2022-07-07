<?php

namespace Comfino\ComfinoGateway\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;

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

    public function write(array $record): void
    {
        if ($record['level'] === $this->loggerType) {
            parent::write($record);
        }
    }
}
