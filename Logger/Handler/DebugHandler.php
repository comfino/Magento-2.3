<?php

namespace Comfino\ComfinoGateway\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;

class DebugHandler extends BaseHandler
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/comfino_payment_debug.log';

    public function write(array $record): void
    {
        if ($record['level'] === $this->loggerType) {
            parent::write($record);
        }
    }
}
