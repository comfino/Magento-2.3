<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Monolog\Handler;

use ComfinoExternal\Monolog\Formatter\FormatterInterface;

interface HandlerInterface
{
    /**
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record);
    /**
     * @param array $record
     * @return bool
     */
    public function handle(array $record);
    /**
     * @param array $records
     */
    public function handleBatch(array $records);
    /**
     * @param callable $callback
     * @return self
     */
    public function pushProcessor($callback);
    /**
     * @return callable
     */
    public function popProcessor();
    /**
     * @param FormatterInterface $formatter
     * @return self
     */
    public function setFormatter(FormatterInterface $formatter);
    /**
     * @return FormatterInterface
     */
    public function getFormatter();
}
