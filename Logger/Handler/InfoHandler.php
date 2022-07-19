<?php

namespace Comfino\ComfinoGateway\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger as MonologLogger;

class InfoHandler extends BaseHandler
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/comfino_payment_info.log';

    public function write(array $record): void
    {
        if ($record['level'] === $this->loggerType) {
            parent::write($record);
        }
    }
}
